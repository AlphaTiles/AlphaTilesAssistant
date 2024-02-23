<?php

namespace App\Http\Controllers;

use App\Models\LanguagePack;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Model;

class BaseItemController extends Controller
{
    public Model $model;
    public string $fileKeyname;
    public string $route;
 
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }
    
    public function delete(LanguagePack $languagePack, Request $request) 
    {        
        if(isset($request->btnCancel)) {
            return redirect("languagepack/{$this->route}/{$languagePack->id}");
        }

        $idsString = $request->deleteIds;
        $ids = explode(',', $idsString);

        foreach($ids as $id) {
            $this->model::where('id', $id)->delete();
        }

        return redirect("languagepack/{$this->route}/{$languagePack->id}");
    }  
}
