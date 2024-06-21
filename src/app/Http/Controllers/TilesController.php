<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Models\Tile;
use App\Models\LanguagePack;
use Illuminate\Http\Request;
use App\Rules\CustomRequired;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use App\Rules\RequireAtLeastOneDistractor;
use App\Services\TileFileUploadService;
use Illuminate\Support\Facades\Log;

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
    public function edit(LanguagePack $languagePack)
    {        
        session()->forget('success');
        
        $tiles = Tile::where('languagepackid', $languagePack->id)->get();

        return view('languagepack.tiles', [
            'completedSteps' => ['lang_info', 'tiles'],
            'languagePack' => $languagePack,
            'tiles' => $tiles    
        ]);
    }

    public function store(LanguagePack $languagePack, Request $request)
    {
        $data = $request->all();
        $tiles = explode("\r\n", $data['add_tiles']);

        $insert = [];
        foreach($tiles as $key => $tile) {
            if(!empty($tile)) {
                $insert[$key]['languagepackid'] = $languagePack->id;
                $insert[$key]['value'] = $tile;
                $insert[$key]['upper'] = strtoupper($tile);
            }
        }

        Tile::insert($insert);
        
        return redirect("languagepack/tiles/{$languagePack->id}");    
    }        

    public function update(LanguagePack $languagePack, Request $request)
    {
        $tiles = $request->all()['tiles'];        
               
        $fileRules = 'mimes:mp3|max:1024';
        $customErrorMessage = "The file upload failed. Please verify that the files are of type mp3 and the file size is not bigger than 1 MB.";
        $validator = Validator::make(
            $request->all(), 
            [
                'tiles.*' => [
                    'required_unless:tiles.*.delete,1',
                    new RequireAtLeastOneDistractor(request()),
                    new CustomRequired(request(), 'type')
                ],
                'tiles.*.languagepackid' => ['required', 'integer'],
                'tiles.*.type' => ['required_unless:tiles.*.delete,1'],
                'tiles.*.or_1' => ['required_unless:tiles.*.delete,1'],
                'tiles.*.or_2' => ['required_unless:tiles.*.delete,1'],
                'tiles.*.or_3' => ['required_unless:tiles.*.delete,1'],
                'tiles.*.type2' => ['sometimes'],    
                'tiles.*.type3' => ['sometimes'],    
                'tiles.*.file' => $fileRules,
                'tiles.*.file2' => $fileRules,
                'tiles.*.file3' => $fileRules,
                'tiles.*.stage' => ['sometimes'],    
                'tiles.*.stage2' => ['sometimes'],    
                'tiles.*.stage3' => ['sometimes'],    
            ],
            [                
                'tiles.*.type' => '',
                'tiles.*.or_1' => '',
                'tiles.*.or_2' => '',
                'tiles.*.or_3' => '',
                'tiles.*.type2' => '',
                'tiles.*.type3' => '',
                'tiles.*.file' => $customErrorMessage,
                'tiles.*.file2' => $customErrorMessage,
                'tiles.*.file3' => $customErrorMessage,
                'tiles.*.stage' => '',    
                'tiles.*.stage2' => '',    
                'tiles.*.stage3' => '',    
            ]
        );

        DB::transaction(function() use($tiles, $fileRules, $languagePack) {
            $fileUploadService = app(TileFileUploadService::class);

            foreach($tiles as $key => $tile) {

                $fileModel1 = $fileUploadService->handle($tile, 1, $fileRules);
                $fileModel2 = $fileUploadService->handle($tile, 2, $fileRules);
                $fileModel3 = $fileUploadService->handle($tile, 3, $fileRules);
                
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

        $tilesCollection = Tile::where('languagepackid', $languagePack->id)->with(['file', 'file2', 'file3'])->get();

        return view('languagepack.tiles', [
            'completedSteps' => ['lang_info', 'tiles'],
            'languagePack' => $languagePack,
            'tiles' => $tilesCollection
        ]);

    }
        
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
}
