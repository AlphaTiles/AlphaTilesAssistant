<?php

namespace App\Http\Controllers;

use App\Enums\TabEnum;
use App\Models\File;
use App\Models\Tile;
use App\Models\LanguagePack;
use Illuminate\Http\Request;
use App\Rules\CustomRequired;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Services\FileUploadService;
use App\Services\ValidationService;
use Illuminate\Support\Facades\Log;
use App\Services\Mp3FileUploadService;
use App\Services\TileFileUploadService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use App\Rules\RequireAtLeastOneDistractor;

class TilesController extends BaseItemController
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(Request $request)
    {
        $this->route = 'tiles';
        $this->model = new Tile();
        $this->fileKeyname = 'tile';

        parent::__construct($request);
    }

    /**
     * Edit the language pack setup.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function edit(LanguagePack $languagePack, string $tile = null)
    {        
        session()->forget('success');
        
        $tiles = Tile::where('languagepackid', $languagePack->id)
        ->when(!empty($tile), function ($query) use ($tile) {
            return $query->where('value', $tile);
        })
        ->paginate(config('pagination.default'));

        $validationErrors = null;
        if(empty($tile)) {
            $validationService = (new ValidationService($languagePack));
            $validationErrors = $validationService->handle(TabEnum::TILE);    
        }

        return view('languagepack.tiles', [
            'completedSteps' => ['lang_info', 'tiles'],
            'languagePack' => $languagePack,
            'items' => $tiles,
            'pagination' => $tiles->links(),
            'validationErrors' => $validationErrors
        ]);
    }

    public function store(LanguagePack $languagePack, Request $request)
    {
        $this->validateAddItems($request, $languagePack, new Tile(), 'tiles');

        $data = $request->all();
        $tiles = explode("\r\n", $data['add_items']);

        $insert = [];
        foreach($tiles as $key => $tile) {
            if(!empty($tile)) {
                $insert[$key]['languagepackid'] = $languagePack->id;
                $insert[$key]['value'] = $tile;
                $insert[$key]['upper'] = strtoupper($tile);
            }
        }

        Tile::insert($insert);
        
        $totalPages = ceil(Tile::where('languagepackid', $languagePack->id)->count() / 10); // Assuming 10 items per page

        return redirect("languagepack/tiles/{$languagePack->id}?page={$totalPages}");    
    }        

    public function update(LanguagePack $languagePack, Request $request)
    {
        $items = $request->all()['items'];        
               
        $fileRules = 'mimes:mp3|max:1024';
        $customErrorMessage = "The file upload failed. Please verify that the files are of type mp3 and the file size is not bigger than 1 MB.";
        $validator = Validator::make(
            $request->all(), 
            [
                'items.*' => [
                    'required_unless:items.*.delete,1',
                    new RequireAtLeastOneDistractor(request()),
                    new CustomRequired(request(), 'type')
                ],
                'items.*.languagepackid' => ['required', 'integer'],
                'items.*.type' => ['required_unless:items.*.delete,1'],
                'items.*.or_1' => ['required_unless:items.*.delete,1'],
                'items.*.or_2' => ['required_unless:items.*.delete,1'],
                'items.*.or_3' => ['required_unless:items.*.delete,1'],
                'items.*.type2' => ['sometimes'],    
                'items.*.type3' => ['sometimes'],    
                'items.*.file' => $fileRules,
                'items.*.file2' => $fileRules,
                'items.*.file3' => $fileRules,
                'items.*.stage' => ['sometimes'],    
                'items.*.stage2' => ['sometimes'],    
                'items.*.stage3' => ['sometimes'],    
            ],
            [                
                'items.*.type' => '',
                'items.*.or_1' => '',
                'items.*.or_2' => '',
                'items.*.or_3' => '',
                'items.*.type2' => '',
                'items.*.type3' => '',
                'items.*.file' => $customErrorMessage,
                'items.*.file2' => $customErrorMessage,
                'items.*.file3' => $customErrorMessage,
                'items.*.stage' => '',    
                'items.*.stage2' => '',    
                'items.*.stage3' => '',    
            ]
        );

        DB::transaction(function() use($items, $fileRules, $languagePack) {
            $fileUploadService = app(FileUploadService::class);

            foreach($items as $key => $tile) {

                $fileModel1 = $fileUploadService->handle($tile, 'tile', 1, $fileRules, 'mp3');
                $fileModel2 = $fileUploadService->handle($tile, 'tile', 2, $fileRules, 'mp3');
                $fileModel3 = $fileUploadService->handle($tile, 'tile', 3, $fileRules, 'mp3');
                
                $updateData = [
                    'upper' => $tile['upper'],
                    'type' => $tile['type'],
                    'stage' => $tile['stage'] ?? null,
                    'or_1' => $tile['or_1'],
                    'or_2' => $tile['or_2'],
                    'or_3' => $tile['or_3'],
                    'file_id' => $tile['file_id'] ?? null,
                    'type2' => $tile['type2'],
                    'file2_id' => $tile['file2_id'] ?? null,
                    'stage2' => $tile['stage2'] ?? null,
                    'type3' => $tile['type3'],
                    'file3_id' => $tile['file3_id'] ?? null,
                    'stage3' => $tile['stage3'] ?? null,                    
                ];                
                
                if (isset($fileModel1->id)) {
                    $updateData['file_id'] = $fileModel1->id;
                }
                if (isset($fileModel2->id)) {
                    $updateData['file2_id'] = $fileModel2->id;
                }
                if (isset($fileModel3->id)) {
                    $updateData['file3_id'] = $fileModel3->id;
                }

                Tile::where(['id' => $tile['id']])
                ->update($updateData);
            }
        });

        if($validator->fails()){
            return Redirect::back()->withErrors($validator)->withInput();
        }
        
        session()->flash('success', 'Records updated successfully');

        $itemsCollection = Tile::where('languagepackid', $languagePack->id)->with(['file', 'file2', 'file3'])->paginate(config('pagination.default'));

        return view('languagepack.tiles', [
            'completedSteps' => ['lang_info', 'tiles'],
            'languagePack' => $languagePack,
            'items' => $itemsCollection,
            'pagination' => $itemsCollection->links()
        ]);

    }        
}
