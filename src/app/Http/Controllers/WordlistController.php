<?php

namespace App\Http\Controllers;

use App\Models\Word;
use App\Enums\TabEnum;
use App\Enums\FileTypeEnum;
use Illuminate\Support\Arr;
use App\Models\LanguagePack;
use Illuminate\Http\Request;
use App\Rules\CustomRequired;
use App\Rules\AudioFileRequired;
use App\Rules\ImageFileRequired;
use App\Models\LanguagepackConfig;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Services\ValidationService;
use Illuminate\Support\Facades\Log;
use App\Services\WordFileUploadService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;

class WordlistController extends BaseItemController
{
    /**
     * Edit the language pack setup.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function edit(LanguagePack $languagePack, string $word = null)
    {        
        $orderBy = $this->getOrderBy($languagePack, 'word_orderby');     
        $words = Word::where('languagepackid', $languagePack->id)
        ->orderBy($orderBy)
        ->when(!empty($word), function ($query) use ($word) {
            return $query->where('value', $word);
        })
        ->paginate(config('pagination.default'));

        $validationErrors = null;
        if(empty($word)) {
            $validationService = (new ValidationService($languagePack));
            $validationErrors = $validationService->handle(TabEnum::WORD);    
        }

        return view('languagepack.wordlist', [
            'completedSteps' => ['lang_info', 'tiles', 'wordlist'],
            'languagePack' => $languagePack,
            'words' => $words,
            'orderby' => $orderBy,
            'pagination' => $words->links(),
            'validationErrors' => $validationErrors
        ]);
    }

    public function store(LanguagePack $languagePack, Request $request)
    {
        $this->validateAddItems($request, $languagePack, new Word(), 'words');

        $data = $request->all();
        $words = explode("\r\n", $data['add_items']);

        $insert = [];
        foreach($words as $key => $word) {
            if(!empty($word)) {
                $insert[$key]['languagepackid'] = $languagePack->id;
                $insert[$key]['value'] = $word;
            }
        }


        Word::insert($insert);

        $totalPages = ceil(Word::where('languagepackid', $languagePack->id)->count() / 10); // Assuming 10 items per page

        return redirect("languagepack/wordlist/{$languagePack->id}?page={$totalPages}");    
    }        

    public function update(LanguagePack $languagePack, Request $request)
    {
        LanguagepackConfig::updateOrCreate(
            [
                'languagepackid' => $languagePack->id,
                'name' => 'word_orderby'
            ],
            [
                'value' => $request->orderBy
            ]
        );

        $words = $request->all()['words'];
        $orderBy = $this->getOrderBy($languagePack, 'word_orderby');          
          
        $validator = Validator::make(
            $request->all(), 
            [
                'words.*' => [
                    'required_unless:words.*.delete,1',
                    new CustomRequired(request(), 'value'),
                    new AudioFileRequired(request(), $words),
                    new ImageFileRequired(request(), $words),
                    'stage' => ['sometimes'],    
                ],
            ],
            [   
                'words.*.value' => '',
                'words.*.mixed_types' => '',
                'words.*.audioFile' => '',
                'words.*.imageFile' => '',
                'words.*.stage' => '',
            ]
        );

        DB::transaction(function() use($languagePack, $words) {
            $fileUploadService = app(WordFileUploadService::class);                        
            $audioRuleClass = new AudioFileRequired(request(), $words);
            $imageRuleClass = new ImageFileRequired(request(), $words);
            foreach($words as $key => $word) {
                $audioFileModel = $fileUploadService->handle($languagePack, $word, $audioRuleClass, FileTypeEnum::AUDIO);
                $imageFileModel = $fileUploadService->handle($languagePack, $word, $imageRuleClass, FileTypeEnum::IMAGE);
                
                $updateData = [
                    'value' => $word['value'] ?? '',
                    'mixed_types' => $word['mixed_types'],
                    'stage' => $word['stage'] ?? null,
                ];
                
                if (isset($audioFileModel->id)) {
                    $updateData['audiofile_id'] = $audioFileModel->id;                    
                }

                if (isset($imageFileModel->id)) {
                    $updateData['imagefile_id'] = $imageFileModel->id;
                }
                
                Word::where(['id' => $word['id']])
                ->update($updateData);
            }
        });

        if($validator->fails()){
            $errorCollection = $validator->errors();
            return Redirect::back()->withErrors($errorCollection)->withInput();
        }
        
        $items = $request->all()['words'];
        if(Arr::pluck($items, 'delete')) {
            $itemsCollection = Word::orderBy($orderBy)
                ->where('languagepackid', $languagePack->id)
                ->paginate(config('pagination.default'));

            return view('languagepack.wordlist', [
                'completedSteps' => ['lang_info', 'tiles', 'wordlist'],
                'languagePack' => $languagePack,
                'words' => $itemsCollection,
                'pagination' => $itemsCollection->links(),
                'orderby' => $orderBy,
            ]);            
        }

        session()->flash('success', 'Records updated successfully');

        return redirect(url('/languagepack/wordlist/' . $languagePack->id) . '?' . http_build_query(request()->query()));
    }
    
    public function delete(LanguagePack $languagePack, Request $request) 
    {        
        if(isset($request->btnCancel)) {
            return redirect("languagepack/wordlist/{$languagePack->id}");
        }

        $wordIdsString = $request->wordIds;
        $wordIds = explode(',', $wordIdsString);

        foreach($wordIds as $wordId) {
            $audioFilename = "word_" .  str_pad($wordId, 3, '0', STR_PAD_LEFT) . ".mp3";
            $audioFile = "languagepacks/{$languagePack->id}/res/raw/{$audioFilename}";
            Storage::disk('public')->delete($audioFile);

            $imageFilename = "word_" .  str_pad($wordId, 3, '0', STR_PAD_LEFT) . ".png";
            $imageFile = "languagepacks/{$languagePack->id}/res/raw/{$imageFilename}";
            Storage::disk('public')->delete($imageFile);

            Word::where('id', $wordId)->delete();
        }

        return redirect(url('/languagepack/wordlist/' . $languagePack->id) . '?' . http_build_query(request()->query()));
    }
}
