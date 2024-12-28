<?php

namespace App\Http\Controllers;

use App\Models\LanguagePack;

class ItemsController extends Controller
{
    public function downloadFile(LanguagePack $languagePack, $filename)
    {        
        $filePath = storage_path("app/public/languagepacks/{$languagePack->id}/res/raw/{$filename}");

        if(file_exists($filePath)) {
            return response()->download($filePath);
        }            
    }
}
