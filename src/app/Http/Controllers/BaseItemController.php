<?php

namespace App\Http\Controllers;

use App\Models\File;
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
            $item = $this->model::find($id);
            for($i = 1; $i <= 3; $i++) {
                $nr = $i > 1 ? $i : '';  
                $fileField = "file{$nr}_id";
                if($item->$fileField) {
                    $fileName = "{$this->fileKeyname}_" .  str_pad($id, 3, '0', STR_PAD_LEFT) . "_{$i}.mp3";
                    $filePath = "languagepacks/{$languagePack->id}/res/raw/{$fileName}";    
                    if (Storage::disk('public')->exists($filePath)) {                        
                        Storage::disk('public')->delete($filePath);                                          
                        File::find($item->$fileField)->delete();
                    }      
                }              
            }
            $this->model::find($id)->delete();
        }

        return redirect("languagepack/{$this->route}/{$languagePack->id}");
    }  

    protected function validateAddItems(Request $request, LanguagePack $languagePack, Model $model, string $itemName) {
        $request->validate([
            'add_items' => [
                'required',
                function ($attribute, $value, $fail) use ($languagePack, $model, $itemName) {
                    $items = explode("\r\n", $value);
                    $duplicates = $model::where('languagepackid', $languagePack->id)
                        ->whereIn('value', $items)
                        ->pluck('value')
                        ->toArray();
                    
                    // Filter the duplicates array to only include exact matches (case and accent sensitive)
                    $duplicates = array_intersect($duplicates, $items);
                    
                    
                    if (!empty($duplicates)) {
                        $fail("The following {$itemName} already exist: " . implode(', ', $duplicates));
                    }
                },
            ],
        ]);

    }
}
