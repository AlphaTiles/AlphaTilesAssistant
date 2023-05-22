<?php

namespace App\Http\Controllers;

use App\Models\LanguagePack;
use Illuminate\Http\Client\Request;

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

    public function store(Request $request)
    {
        $data = $request->all();
        dd($data);
        

        //return redirect("languagepack/tiles/{$languagePack->id}");    
    }        
}
