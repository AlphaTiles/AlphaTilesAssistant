<?php

namespace App\Http\Controllers;

use App\Models\Syllable;
use App\Models\LanguagePack;
use Illuminate\Http\Request;
use App\Rules\CustomRequired;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;

class SyllablesController extends BaseItemController
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(Request $request)
    {
        $this->route = 'syllables';
        $this->model = new Syllable();
        $this->fileKeyname = 'syllable';

        parent::__construct($request);
    }

    /**
     * Edit the language pack setup.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function edit(LanguagePack $languagePack)
    {        
        session()->forget('success');
        
        $items = $this->model::where('languagepackid', $languagePack->id)->paginate(config('pagination.default'));

        return view('languagepack.' . $this->route, [
            'completedSteps' => ['lang_info', 'syllables'],
            'languagePack' => $languagePack,
            'syllables' => $items,
            'pagination' => $items->links()
        ]);
    }

    public function store(LanguagePack $languagePack, Request $request)
    {
        $data = $request->all();
        $items = explode("\r\n", $data['add_syllables']);

        $insert = [];
        foreach($items as $key => $item) {
            if(!empty($item)) {
                $insert[$key]['languagepackid'] = $languagePack->id;
                $insert[$key]['value'] = $item;
            }
        }

        $this->model::insert($insert);
        
        $totalPages = ceil($this->model::where('languagepackid', $languagePack->id)->count() / 10); // Assuming 10 items per page

        return redirect("languagepack/syllables/{$languagePack->id}?page={$totalPages}");    
    }        

    public function update(LanguagePack $languagePack, Request $request)
    {
        $syllables = $request->all()['items'];        
               
        $fileRules = 'mimes:mp3|max:1024';
        $customErrorMessage = "The file upload failed. Please verify that the files are of type mp3 and the file size is not bigger than 1 MB.";
        $validator = Validator::make(
            $request->all(), 
            [
                'items.*' => [
                    'required_unless:items.*.delete,1',
                    new CustomRequired(request(), 'or_1', 'Or1'),
                    new CustomRequired(request(), 'or_2', 'Or2'),
                    new CustomRequired(request(), 'or_3', 'Or3'),
                    new CustomRequired(request(), 'color')
                ],
                'items.*.languagepackid' => ['required', 'integer'],
                'items.*.or_1' => ['required_unless:items.*.delete,1'],
                'items.*.or_2' => ['required_unless:items.*.delete,1'],
                'items.*.or_3' => ['required_unless:items.*.delete,1'],
                // 'items.*.file' => $fileRules,
                'items.*.color' => ['required_unless:keys.*.delete,1'],
            ],
            [                
                'items.*.or_1' => '',
                'items.*.or_2' => '',
                'items.*.or_3' => '',
                // 'items.*.file' => $customErrorMessage,
                'items.*.color' => '',
            ]
        );

        DB::transaction(function() use($syllables, $fileRules, $languagePack) {
            // $fileUploadService = app(SyllableFileUploadService::class);

            foreach($syllables as $key => $syllable) {

                // $fileModel1 = $fileUploadService->handle($syllable, 1, $fileRules);
                
                $updateData = [
                    'or_1' => $syllable['or_1'],
                    'or_2' => $syllable['or_2'],
                    'or_3' => $syllable['or_3'],
                    // 'file_id' => $syllable['file_id'] ?? null,
                    'color' => $syllable['color'],
                ];                
                
                // if (isset($fileModel1->id)) {
                //     $updateData['file_id'] = $fileModel1->id;
                // }

                Syllable::where(['id' => $syllable['id']])
                ->update($updateData);
            }
        });

        if($validator->fails()){
            return Redirect::back()->withErrors($validator)->withInput();
        }
        
        session()->flash('success', 'Records updated successfully');

        $syllablesCollection = Syllable::where('languagepackid', $languagePack->id)->with(['file'])->paginate(config('pagination.default'));

        return view('languagepack.syllables', [
            'completedSteps' => ['lang_info', 'syllables'],
            'languagePack' => $languagePack,
            'syllables' => $syllablesCollection,
            'pagination' => $syllablesCollection->links()
        ]);

    }
     
    /*
    public function delete(LanguagePack $languagePack, Request $request) 
    {        
        if(isset($request->btnCancel)) {
            return redirect("languagepack/{$this->route}/{$languagePack->id}");
        }

        $idsString = $request->deleteIds;
        $ids = explode(',', $idsString);

        foreach($ids as $id) {
            for($i = 1; $i <= 3; $i++) {
                $fileName = "{$this->fileKeyname}_" .  str_pad($id, 3, '0', STR_PAD_LEFT) . "_{$i}.mp3";
                $file = "languagepacks/{$languagePack->id}/res/raw/{$fileName}";    
                if (Storage::disk('public')->exists($file)) {    
                    Storage::disk('public')->delete($file);    
                }                    
            }
            $this->model::where('id', $id)->delete();
        }

        return redirect("languagepack/{$this->route}/{$languagePack->id}");
    }  

    public function downloadFile(LanguagePack $languagePack, $filename)
    {        
        $filePath = storage_path("app/public/languagepacks/{$languagePack->id}/res/raw/{$filename}");

        if(file_exists($filePath)) {
            return response()->download($filePath);
        }            
    }
    */
}
