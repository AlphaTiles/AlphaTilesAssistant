<?php

namespace App\Http\Controllers;

use App\Enums\FileTypeEnum;
use App\Models\Word;
use App\Rules\AudioFileRequired;
use App\Models\LanguagePack;
use Illuminate\Http\Request;
use App\Rules\CustomRequired;
use App\Rules\ImageFileRequired;
use App\Services\FileUploadService;
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
        $words = Word::where('languagepackid', $languagePack->id)->get();

        return view('languagepack.wordlist', [
            'completedSteps' => ['lang_info', 'tiles', 'wordlist'],
            'languagePack' => $languagePack,
            'words' => $words    
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
                $arrWord = explode(';', $word);
                $insert[$key]['value'] = $arrWord[0];
                $insert[$key]['translation'] = $arrWord[1] ?? '';
            }
        }

        Word::insert($insert);

        if($request->btnBack) {
            return redirect("languagepack/tiles/{$languagePack->id}");    
        }

        if($request->btnNext) {
            return redirect("languagepack/export/{$languagePack->id}");    
        }

        return redirect("languagepack/wordlist/{$languagePack->id}");    
    }        

    public function update(LanguagePack $languagePack, Request $request)
    {
        $words = $request->all()['words'];
          
        $validator = Validator::make(
            $request->all(), 
            [
                'words.*' => [
                    'required_unless:words.*.delete,1',
                    new CustomRequired(request(), 'translation'),
                    new AudioFileRequired(request()),
                    new ImageFileRequired(request()),
                ],
                'words.*.translation' => ['required_unless:words.*.delete,1'],
            ],
            [                
                'words.*.translation' => '',
                'words.*.mixed_types' => '',
                'words.*.audioFile' => '',
                'words.*.imageFile' => '',
            ]
        );

        DB::transaction(function() use($languagePack, $words) {
            $fileUploadService = app(FileUploadService::class);                        
            $audioRuleClass = new AudioFileRequired(request());
            $imageRuleClass = new ImageFileRequired(request());
            foreach($words as $key => $word) {
                $audioFileModel = $fileUploadService->handle($languagePack, $word, $audioRuleClass, FileTypeEnum::AUDIO);
                $imageFileModel = $fileUploadService->handle($languagePack, $word, $imageRuleClass, FileTypeEnum::IMAGE);
                
                $updateData = [
                    'translation' => $word['translation'] ?? '',
                    'mixed_types' => $word['mixed_types'],
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

        $wordCollection = Collection::make($words)->map(function ($item) {
            if(isset($item['audioFile'])) {
                $item['audioFilename'] = $item['audioFile']->getClientOriginalName();
            }
            if(isset($item['imageFile'])) {
                $item['imageFilename'] = $item['imageFile']->getClientOriginalName();
            }
            return (object) $item;
        });

        return view('languagepack.wordlist', [
            'completedSteps' => ['lang_info', 'tiles', 'words'],
            'languagePack' => $languagePack,
            'words' => $wordCollection
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
            $word = Word::find($wordId);
            $audioFilename = strtolower(preg_replace("/\s+/", "", $word->translation));
            $newAudioFileName = $audioFilename . '.mp3';
            $audioFile = "languagepacks/{$languagePack->id}/res/raw/{$newAudioFileName}";
            Storage::disk('public')->delete($audioFile);

            $imageFilename = strtolower(preg_replace("/\s+/", "", $word->translation));
            $newImageFileName = $imageFilename . '.png';
            $imageFile = "languagepacks/{$languagePack->id}/res/raw/{$newImageFileName}";
            Storage::disk('public')->delete($imageFile);

            Word::where('id', $wordId)->delete();
        }

        return redirect("languagepack/wordlist/{$languagePack->id}");
    }
    
    public function downloadFile(LanguagePack $languagePack, $filename)
    {        
        $filePath = storage_path("app/public/languagepacks/{$languagePack->id}/res/raw/{$filename}");

        return response()->download($filePath, null, ['Cache-Control' => 'no-cache, must-revalidate']);
    }
}
