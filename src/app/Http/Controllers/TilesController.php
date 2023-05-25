<?php

namespace App\Http\Controllers;

use App\Models\Tile;
use App\Models\LanguagePack;
use Illuminate\Http\Request;

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
        return view('languagepack.tiles', [
            'completedSteps' => ['lang_info', 'tiles'],
            'id' => $languagePack->id
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
}
