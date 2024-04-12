<?php

namespace App\Http\Controllers;

use App\Enums\FileTypeEnum;
use App\Models\Word;
use App\Rules\AudioFileRequired;
use App\Models\LanguagePack;
use Illuminate\Http\Request;
use App\Rules\CustomRequired;
use App\Rules\ImageFileRequired;
use App\Services\WordFileUploadService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;

class WordlistController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Edit the language pack setup.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function edit(LanguagePack $languagePack)
    {        
        $words = Word::where('languagepackid', $languagePack->id)->paginate(config('pagination.default'));

        return view('languagepack.wordlist', [
            'completedSteps' => ['lang_info', 'tiles', 'wordlist'],
            'languagePack' => $languagePack,
            'words' => $words,
            'pagination' => $words->links()

        ]);
    }

    public function store(LanguagePack $languagePack, Request $request)
    {
        $data = $request->all();
        $words = explode("\r\n", $data['add_words']);

        $insert = [];
        foreach($words as $key => $word) {
            if(!empty($word)) {
                $insert[$key]['languagepackid'] = $languagePack->id;
                $insert[$key]['value'] = $word;
            }
        }

        Word::insert($insert);

        $totalPages = ceil(Word::count() / 10); // Assuming 10 items per page

        return redirect("languagepack/wordlist/{$languagePack->id}?page={$totalPages}");    
    }        

    public function update(LanguagePack $languagePack, Request $request)
    {
        $words = $request->all()['words'];
          
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
        
        session()->flash('success', 'Records updated successfully');

        $wordCollection = Word::where('languagepackid', $languagePack->id)->paginate(config('pagination.default'));

        return view('languagepack.wordlist', [
            'completedSteps' => ['lang_info', 'tiles', 'wordlist'],
            'languagePack' => $languagePack,
            'words' => $wordCollection,
            'pagination' => $wordCollection->links()
        ]);
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

        return Redirect::back();
    }
    
    public function downloadFile(LanguagePack $languagePack, $filename)
    {        
        $filePath = storage_path("app/public/languagepacks/{$languagePack->id}/res/raw/{$filename}");

        return response()->download($filePath, null, ['Cache-Control' => 'no-cache, must-revalidate']);
    }
}
