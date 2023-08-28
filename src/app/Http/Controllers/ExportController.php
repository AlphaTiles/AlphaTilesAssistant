<?php

namespace App\Http\Controllers;

use ZipArchive;
use App\Models\File;
use App\Models\Tile;
use App\Models\Word;
use App\Rules\FileRequired;
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

class ExportController extends Controller
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
     * Show the Export Page
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function show(LanguagePack $languagePack)
    {        
        return view('languagepack.export', [
            'completedSteps' => ['lang_info', 'tiles', 'wordlist', 'export'],
            'id' => $languagePack->id,
        ]);
    }

    public function store(LanguagePack $languagePack) 
    {
        $zipFileName = $languagePack->name;
        $zipFile = sys_get_temp_dir() . '/' . $zipFileName;

        $zip = new ZipArchive();
        $zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);
                
        $resourceFile = 'app/public/languagepacks/4/res/raw/culebra.mp3';
        $outputFolder = $zipFileName . '/res/raw/culebra.mp3';
        $zip->addFile(storage_path($resourceFile), $outputFolder);
        $zip->close();
        
        return response()->download($zipFile);        
    }
}
