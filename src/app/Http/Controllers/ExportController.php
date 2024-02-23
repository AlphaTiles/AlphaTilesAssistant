<?php

namespace App\Http\Controllers;

use App\Models\LanguagePack;
use App\Services\GenerateZipExportService;

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
            'completedSteps' => ['lang_info', 'tiles', 'wordlist', 'keyboard', 'export'],
            'languagePack' => $languagePack,
        ]);
    }

    public function store(LanguagePack $languagePack) 
    {
        $zipFile = (new GenerateZipExportService($languagePack))->handle();
        
        return response()->download($zipFile);        
    }
}
