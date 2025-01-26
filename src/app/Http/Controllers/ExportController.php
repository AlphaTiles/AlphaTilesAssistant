<?php

namespace App\Http\Controllers;

use App\Models\Word;
use App\Models\LanguagePack;
use Illuminate\Support\MessageBag;
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
        $wordsWithoutFile = Word::where('languagepackid', $languagePack->id)
            ->where(function ($query) {
                $query->whereNull('audiofile_id')
                    ->orWhereNull('imagefile_id');
            })        
            ->get();

        $errors = [];
        $i = 0;
        foreach($wordsWithoutFile as $word) {
            if(empty($word->audiofile_id)) {
                $errors[$i] = "An audio file is required for {$word->value}.";
                $i++;
            }
            if(empty($word->audiofile_id)) {
                $errors[$i] = "An image file is required for {$word->value}.";
                $i++;
            }
        }

        $errorBag = new MessageBag(['audiofile_id' => $errors]);

        return view('languagepack.export', [
            'completedSteps' => ['lang_info', 'tiles', 'wordlist', 'keyboard', 'syllables', 'resources', 'game_settings', 'export'],            
            'languagePack' => $languagePack,
            'errors' => $errorBag
        ]);
    }

    public function store(LanguagePack $languagePack) 
    {
        $zipFile = (new GenerateZipExportService($languagePack))->handle();
        
        return response()->download($zipFile);        
    }
}
