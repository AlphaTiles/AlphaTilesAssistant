<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Models\Tile;
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

class TilesController extends Controller
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
        $tiles = Tile::where('languagepackid', $languagePack->id)->get();

        return view('languagepack.tiles', [
            'completedSteps' => ['lang_info', 'tiles'],
            'id' => $languagePack->id,
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
        
        if($request->btnBack) {
            return redirect("languagepack/edit/{$languagePack->id}");    
        }

        if($request->btnNext) {
            return redirect("languagepack/wordlist/{$languagePack->id}");    
        }

        return redirect("languagepack/tiles/{$languagePack->id}");    
    }        

    public function update(LanguagePack $languagePack, Request $request)
    {
        $tiles = $request->all()['tiles'];
               
        $fileRules = ['tiles.*.file' => 'mimes:mp3|max:1024'];
        $customErrorMessage = "The file upload failed. Please verify that the files are of type mp3 and the file size is not bigger than 1 MB.";
        $validator = Validator::make(
            $request->all(), 
            [
                'tiles.*' => [
                    'required_unless:tiles.*.delete,1',
                    new RequireAtLeastOneDistractor(request()),
                    new CustomRequired(request(), 'type')
                ],
                'tiles.*.type' => ['required_unless:tiles.*.delete,1'],
                'tiles.*.or_1' => ['required_unless:tiles.*.delete,1'],
                'tiles.*.or_2' => ['required_unless:tiles.*.delete,1'],
                'tiles.*.or_3' => ['required_unless:tiles.*.delete,1'],
                'tiles.*.type2' => ['sometimes'],    
                'tiles.*.type3' => ['sometimes'],    
            ] + $fileRules,
            [                
                'tiles.*.type' => '',
                'tiles.*.or_1' => '',
                'tiles.*.or_2' => '',
                'tiles.*.or_3' => '',
                'tiles.*.type2' => '',
                'tiles.*.type3' => '',
                'tiles.*.file' => $customErrorMessage,
            ]
        );

        DB::transaction(function() use($languagePack, $tiles, $fileRules, $validator) {
            foreach($tiles as $key => $tile) {
                $fileModel = new File;
                if(isset($tile['file'])) {
                    $fileValdidation = Validator::make(['tiles' => [$tile]], $fileRules);                        
                        if($fileValdidation->passes()){
                            $newFileName = "tile_" .  str_pad($tile['id'], 3, '0', STR_PAD_LEFT) . '.mp3';
                            $languagePackPath = "languagepacks/{$languagePack->id}/res/raw/";
                            $filePath = $tile['file']->storeAs($languagePackPath, $newFileName, 'public');
                            $fileModel->name = $tile['file']->getClientOriginalName();
                            $fileModel->file_path = '/storage/' . $filePath;
                            $fileModel->save();
                        }           
                }

                $updateData = [
                    'upper' => $tile['upper'],
                    'type' => $tile['type'],
                    'or_1' => $tile['or_1'],
                    'or_2' => $tile['or_2'],
                    'or_3' => $tile['or_3'],
                    'type2' => $tile['type2'],
                    'type3' => $tile['type3'],
                ];
                
                if (isset($fileModel->id)) {
                    $updateData['file_id'] = $fileModel->id;
                }
                
                Tile::where(['id' => $tile['id']])
                ->update($updateData);
            }
        });

        if($validator->fails()){
            return Redirect::back()->withErrors($validator)->withInput();
        }
        
        session()->flash('success', 'Records updated successfully');

        $tilesCollection = Collection::make($tiles)->map(function ($item) {
            if(isset($item['file'])) {
                $item['filename'] = $item['file']->getClientOriginalName();
            }
            return (object) $item;
        });


        return view('languagepack.tiles', [
            'completedSteps' => ['lang_info', 'tiles'],
            'id' => $languagePack->id,
            'tiles' => $tilesCollection
        ]);

    }
    
    public function delete(LanguagePack $languagePack, Request $request) 
    {        
        if(isset($request->btnCancel)) {
            return redirect("languagepack/tiles/{$languagePack->id}");
        }

        $tileIdsString = $request->tileIds;
        $tileIds = explode(',', $tileIdsString);

        foreach($tileIds as $tileId) {
            $fileName = "tile_" .  str_pad($tileId, 3, '0', STR_PAD_LEFT) . '.mp3';
            $file = "languagepacks/{$languagePack->id}/res/raw/{$fileName}";
            Storage::disk('public')->delete($file);
            Tile::where('id', $tileId)->delete();
        }

        return redirect("languagepack/tiles/{$languagePack->id}");
    }
    
    public function downloadFile(LanguagePack $languagePack, $filename)
    {        
        $filePath = storage_path("app/public/languagepacks/{$languagePack->id}/res/raw/{$filename}");

        return response()->download($filePath);
    }
}
