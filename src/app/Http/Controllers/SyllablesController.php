<?php

namespace App\Http\Controllers;

use App\Enums\TabEnum;
use App\Models\Syllable;
use App\Models\LanguagePack;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\FileUploadService;
use App\Services\ValidationService;
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
    public function edit(LanguagePack $languagePack, string $syllable = null)
    {        
        session()->forget('success');        
        
        $items = $this->model::where('languagepackid', $languagePack->id)
        ->when(!empty($syllable), function ($query) use ($syllable) {
            return $query->where('value', $syllable);
        })        
        ->paginate(config('pagination.default'));

        $validationErrors = null;
        if(empty($syllable)) {
            $validationService = (new ValidationService($languagePack));
            $validationErrors = $validationService->handle(TabEnum::SYLLABLE);    
        }


        return view('languagepack.' . $this->route, [
            'completedSteps' => ['lang_info', 'tiles', 'wordlist', 'keyboard', 'syllables'],
            'languagePack' => $languagePack,
            'syllables' => $items,
            'pagination' => $items->links(),
            'validationErrors' => $validationErrors
        ]);
    }

    public function store(LanguagePack $languagePack, Request $request)
    {
        $this->validateAddItems($request, $languagePack, new Syllable(), 'syllables');

        $data = $request->all();
        $items = explode("\r\n", $data['add_items']);

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
                ],
                'items.*.languagepackid' => ['required', 'integer'],
                'items.*.file' => $fileRules,
                'items.*.color' => ['sometimes'],
            ],
            [                
                'items.*.or_1' => '',
                'items.*.or_2' => '',
                'items.*.or_3' => '',
                'items.*.file' => $customErrorMessage,
                'items.*.color' => '',
            ]
        );

        DB::transaction(function() use($syllables, $fileRules, $languagePack) {
            $fileUploadService = app(FileUploadService::class);

            foreach($syllables as $key => $syllable) {

                $fileModel1 = $fileUploadService->handle($syllable, 'syllable', 1, $fileRules, 'mp3');
                
                $updateData = [
                    'or_1' => $syllable['or_1'],
                    'or_2' => $syllable['or_2'],
                    'or_3' => $syllable['or_3'],
                    'file_id' => $syllable['file_id'] ?? null,
                    'color' => $syllable['color'],
                ];                
                
                if (isset($fileModel1->id)) {
                    $updateData['file_id'] = $fileModel1->id;
                }

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
            'completedSteps' => ['lang_info', 'tiles', 'wordlist', 'keyboard', 'syllables'],
            'languagePack' => $languagePack,
            'syllables' => $syllablesCollection,
            'pagination' => $syllablesCollection->links()
        ]);

    }
}
