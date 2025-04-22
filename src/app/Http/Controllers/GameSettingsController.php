<?php

namespace App\Http\Controllers;

use App\Models\GameSetting;
use App\Models\LanguagePack;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Repositories\GameSettingsRepository;
use App\Http\Requests\StoreGameSettingsRequest;

class GameSettingsController extends Controller
{
    protected $gameSettingsRepository;
    
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(GameSettingsRepository $gameSettingsRepository)
    {
        $this->middleware('auth');

        $this->gameSettingsRepository = $gameSettingsRepository;
    }

    /**
     * Edit the game settings
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function edit(LanguagePack $languagePack)
    {       
        $create = !GameSetting::where('languagepackid', $languagePack->id)->exists();

        return view('languagepack.game_settings', [
            'languagePack' => $languagePack,
            'completedSteps' => ['lang_info', 'tiles', 'wordlist', 'keyboard', 'syllables', 'resources', 'game_settings'],
            'settings' => $this->gameSettingsRepository->getSettings($create, $languagePack),
            'tiles' => ''
        ]);
    }

    public function update(LanguagePack $languagePack, StoreGameSettingsRequest $request)
    {
        $this->saveSettings($languagePack, $request);

        session()->flash('success', 'Records updated successfully');    

        return redirect("languagepack/game_settings/{$languagePack->id}");    
    }    
    
    private function saveSettings(LanguagePack $languagePack, StoreGameSettingsRequest $request): void
    {
        GameSetting::where('languagepackid', $languagePack->id)
            ->delete();

        $settings = [];

        if(isset($request->settings)) {
            $key = 0;
            foreach($request->settings as $key => $setting) {
                if(!empty($setting)) {
                    $settings[$key]['languagepackid'] = $languagePack->id;
                    $settings[$key]['name'] = $key;
                    $settings[$key]['value'] = $setting;
                    $key++;
                }
            }
            
            GameSetting::insert($settings);
        }
    }
}
