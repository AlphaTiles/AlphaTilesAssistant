<?php

namespace App\Http\Controllers;

use App\Enums\ErrorTypeEnum;
use App\Enums\ErrorLevelEnum;
use App\Enums\GameSettingEnum;
use App\Models\GameSetting;
use App\Models\LanguagePack;
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
        $validationService = (new ValidationService($languagePack));
        $errors = collect($validationService->handle())->flatten(1);

        if (empty($appId)) {
            $errors->push([
                'value' => '',
                'type' => ErrorTypeEnum::MISSING_APP_ID,
                'tab' => ErrorTypeEnum::MISSING_APP_ID->tab()->name(),
            ]);
        }

        $groupedErrors = $errors
            ->sortBy('tab')
            ->groupBy(fn (array $error) => $error['type']->value)
            ->toArray();

        $errorLevels = collect(array_keys($groupedErrors))
            ->map(fn (string $errorType) => ErrorTypeEnum::from($errorType)->level());

        $hasCriticalErrors = $errorLevels->contains(ErrorLevelEnum::CRITICAL);
        $hasWarningErrors = $errorLevels->contains(ErrorLevelEnum::WARNING);

        $exportWarningMessage = null;
        $exportWarningLevel = null;
        if ($hasCriticalErrors) {
            $exportWarningMessage = 'Warning! Your language pack contains critical errors. Apps built with this language pack will function poorly, including crashes. Do you still want to proceed?';
            $exportWarningLevel = ErrorLevelEnum::CRITICAL->value;
        } elseif ($hasWarningErrors) {
            $exportWarningMessage = 'Warning! Your language pack contains warnings. Apps built with this language pack may function poorly, including crashes. Do you still want to proceed?';
            $exportWarningLevel = ErrorLevelEnum::WARNING->value;
        }

        return view('languagepack.export', [
            'completedSteps' => ['lang_info', 'tiles', 'wordlist', 'keyboard', 'syllables', 'resources', 'game_settings', 'games', 'export'],            
            'languagePack' => $languagePack,
            'errors' => $groupedErrors,
            'exportWarningMessage' => $exportWarningMessage,
            'exportWarningLevel' => $exportWarningLevel,
        ]);
    }

    public function store(LanguagePack $languagePack) 
    {
        $zipFile = (new GenerateZipExportService($languagePack))->handle();
        
        return response()->download($zipFile);        
    }
}
