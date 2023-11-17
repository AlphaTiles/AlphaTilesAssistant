<?php

namespace App\Http\Controllers;

use App\Enums\FileTypeEnum;
use App\Models\File;
use App\Models\Tile;
use App\Models\Word;
use App\Rules\AudioFileRequired;
use App\Models\LanguagePack;
use Illuminate\Http\Request;
use App\Rules\CustomRequired;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use App\Rules\RequireAtLeastOneDistractor;

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
                $arrWord = preg_split("/\t+/", $word);
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
                ],
                'words.*.translation' => ['required_unless:words.*.delete,1'],
            ],
            [                
                'words.*.translation' => '',
                'words.*.mixed_types' => '',
                'words.*.audioFile' => '',
            ]
        );

        DB::transaction(function() use($languagePack, $words) {
            foreach($words as $key => $word) {
                $fileModel = new File;
                if(isset($word['audioFile'])) {
                    $fileRules = ['words.*.audioFile' => [            
                        new AudioFileRequired(request()),
                    ]];            
                    $fileValdidation = Validator::make(['words' => [$word]], $fileRules);                        
                        if($fileValdidation->passes()){
                            $filename = strtolower(preg_replace("/\s+/", "", $word['translation']));
                            $newFileName = $filename . '.mp3';
                            $languagePackPath = "languagepacks/{$languagePack->id}/res/raw/";
                            $filePath = $word['audioFile']->storeAs($languagePackPath, $newFileName, 'public');
                            $fileModel->name = $word['audioFile']->getClientOriginalName();
                            $fileModel->type = FileTypeEnum::AUDIO->value;
                            $fileModel->file_path = '/storage/' . $filePath;
                            $fileModel->save();
                        }           
                }

                $updateData = [
                    'translation' => $word['translation'] ?? '',
                    'mixed_types' => $word['mixed_types'],
                ];
                
                if (isset($fileModel->id)) {
                    $updateData['file_id'] = $fileModel->id;
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
            if(isset($item['file'])) {
                $item['audioFilename'] = $item['file']->getClientOriginalName();
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
            $fileName = "word_" .  str_pad($wordId, 3, '0', STR_PAD_LEFT) . '.mp3';
            $word = Word::find($wordId);
            $filename = strtolower(preg_replace("/\s+/", "", $word->translation));
            $newFileName = $filename . '.mp3';
            $file = "languagepacks/{$languagePack->id}/res/raw/{$newFileName}";
            Storage::disk('public')->delete($file);
            Word::where('id', $wordId)->delete();
        }

        return redirect("languagepack/wordlist/{$languagePack->id}");
    }
    
    public function downloadFile(LanguagePack $languagePack, $filename)
    {        
        $filePath = storage_path("app/public/languagepacks/{$languagePack->id}/res/raw/{$filename}");

        return response()->download($filePath);
    }
}
