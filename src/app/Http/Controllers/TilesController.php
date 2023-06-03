<?php

namespace App\Http\Controllers;

use App\Models\Tile;
use App\Models\LanguagePack;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
            $insert[$key]['languagepackid'] = $languagePack->id;
            $insert[$key]['value'] = $tile;
            $insert[$key]['upper'] = strtoupper($tile);
        }

        Tile::insert($insert);

        return redirect("languagepack/tiles/{$languagePack->id}");    
    }        

    public function update(LanguagePack $languagePack, Request $request)
    {
        $tiles = $request->all()['tiles'];

        DB::transaction(function() use($tiles) {
            foreach($tiles as $key => $tile) {
                Tile::where(['id' => $tile['id']])
                    ->update([
                        'upper' => $tile['upper'],
                        'type' => $tile['type'],
                        'or_1' => $tile['or_1'],
                        'or_2' => $tile['or_2'],
                        'or_3' => $tile['or_3'],
                    ]);
            }
        });

        session()->flash('success', 'Records updated successfully');

        return redirect("languagepack/tiles/{$languagePack->id}");    
    }        
}
