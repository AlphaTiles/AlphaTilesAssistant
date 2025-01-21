<?php

namespace App\Http\Controllers;

use App\Models\Resource;
use App\Models\LanguagePack;
use Illuminate\Http\Request;
use App\Rules\CustomRequired;
use Illuminate\Support\Facades\DB;
use App\Services\FileUploadService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use App\Rules\RequireAtLeastOneDistractor;

class ResourcesController extends BaseItemController
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(Request $request)
    {
        $this->route = 'resources';
        $this->model = new Resource();
        $this->fileKeyname = 'resource';

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
            'completedSteps' => ['lang_info', 'tiles', 'wordlist', 'keyboard', 'syllables', 'resources'],
            'languagePack' => $languagePack,
            'items' => $items,
            'pagination' => $items->links()
        ]);
    }

    public function store(LanguagePack $languagePack, Request $request)
    {
        $data = $request->all();
        $items = explode("\r\n", $data['add_resources']);

        $insert = [];
        foreach($items as $key => $item) {
            if(!empty($item)) {
                $insert[$key]['languagepackid'] = $languagePack->id;
                $insert[$key]['link'] = $item;
            }
        }

        $this->model::insert($insert);
        
        $totalPages = ceil($this->model::where('languagepackid', $languagePack->id)->count() / 10); // Assuming 10 items per page

        return redirect("languagepack/resources/{$languagePack->id}?page={$totalPages}");    
    }        

    public function update(LanguagePack $languagePack, Request $request)
    {
        $items = $request->all()['items'];        
               
        $fileRules = 'mimes:png|max:512';
        $customErrorMessage = "The file upload failed. Please verify that the files are of type png and the file size is not bigger than 512kb.";
        $validator = Validator::make(
            $request->all(), 
            [
                'items.*' => [
                    'required_unless:items.*.delete,1',
                ],
                'items.*.languagepackid' => ['required', 'integer'],
                'items.*.name' => ['required_unless:items.*.delete,1'],
                'items.*.link' => ['url', 'required_unless:items.*.delete,1'],
                'items.*.file' => $fileRules,
            ],
            [                
                'items.*.name' => 'The name field is required.',
                'items.*.link' => 'Please enter a valid link.',
                'items.*.file' => $customErrorMessage,
            ]
        );

        DB::transaction(function() use($items, $fileRules, $languagePack) {
            $fileUploadService = app(FileUploadService::class);

            foreach($items as $key => $item) {

                $fileModel1 = $fileUploadService->handle($item, 'resource', 1, $fileRules, 'png');
                
                $updateData = [
                    'name' => $item['name'],
                    'link' => $item['link'],
                    'file_id' => $item['file_id'] ?? null,
                ];                
                                
                if (isset($fileModel1->id)) {
                    $updateData['file_id'] = $fileModel1->id;
                }

                $this->model::where(['id' => $item['id']])
                ->update($updateData);
            }
        });

        if($validator->fails()){
            return Redirect::back()->withErrors($validator)->withInput();
        }
        
        session()->flash('success', 'Records updated successfully');

        $itemCollection = $this->model::where('languagepackid', $languagePack->id)->with(['file'])->paginate(config('pagination.default'));

        return view('languagepack.resources', [
            'completedSteps' => ['lang_info', 'tiles', 'wordlist', 'keyboard', 'syllables', 'resources'],
            'languagePack' => $languagePack,
            'items' => $itemCollection,
            'pagination' => $itemCollection->links()
        ]);

    }
}
