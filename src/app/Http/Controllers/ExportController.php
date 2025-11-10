<?php

namespace App\Http\Controllers;

use App\Enums\ErrorTypeEnum;
use App\Enums\GameSettingEnum;
use App\Models\GameSetting;
use App\Models\Word;
use App\Models\LanguagePack;
use Illuminate\Support\MessageBag;
use App\Services\GenerateZipExportService;
use App\Services\ValidationService;

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
        $appId = GameSetting::where('languagepackid', $languagePack->id)
            ->where('name', GameSettingEnum::APP_ID)->first()?->value ?? '';

        if(empty($appId)) {
            $errors[0]['value'] = '';
            $errors[0]['type'] = ErrorTypeEnum::MISSING_APP_ID; 
            $errors[0]['tab'] = ErrorTypeEnum::MISSING_APP_ID->tab()->name();
            $groupedErrors = collect($errors)->sortBy('tab')->groupBy('type');

            return view('languagepack.export', [
                'completedSteps' => ['lang_info', 'tiles', 'wordlist', 'keyboard', 'syllables', 'resources', 'game_settings', 'export'],            
                'languagePack' => $languagePack,
                'errors' => $groupedErrors->toArray(),
                'criticalError' => true
            ]);
        }

        $validationService = (new ValidationService($languagePack));
        $errors = $validationService->handle();
        

        return view('languagepack.export', [
            'completedSteps' => ['lang_info', 'tiles', 'wordlist', 'keyboard', 'syllables', 'resources', 'game_settings', 'export'],            
            'languagePack' => $languagePack,
            'errors' => $errors
        ]);
    }

    public function store(LanguagePack $languagePack) 
    {
        $zipFile = (new GenerateZipExportService($languagePack))->handle();
        
        return response()->download($zipFile);        
    }
}
