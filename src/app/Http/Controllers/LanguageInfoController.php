<?php

namespace App\Http\Controllers;

use App\Enums\LangInfoEnum;
use App\Models\LanguagePack;
use App\Models\LanguageSetting;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\StoreLangPackRequest;
use Illuminate\Support\Facades\Log;

class LanguageInfoController extends Controller
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
     * Create the language pack setup.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function create()
    {        
        return view('languagepack.info', [
            'id' => '',
            'completedSteps' => ['lang_info'],
            'settings' => $this->getSettings(true),
            'tiles' => ''
        ]);
    }

    /**
     * Edit the language pack setup.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function edit(LanguagePack $languagePack)
    {        
        return view('languagepack.info', [
            'id' => $languagePack->id,
            'completedSteps' => ['lang_info'],
            'settings' => $this->getSettings(false),
            'tiles' => ''
        ]);
    }

    public function store(StoreLangPackRequest $request)
    {
        $data = $request->all();
        $settings = $data['settings'];

        $languagePack = [
            'userid' => Auth::user()->id,
            'name' => $settings['lang_name_english']
        ];

        if(isset($data['id'])) {
            $languagePackSaved = LanguagePack::find($data['id']);
            $languagePackSaved->update($languagePack);
        } else {
            $languagePackSaved = LanguagePack::create($languagePack);
        }

        $this->saveSettings($languagePackSaved, $request);

        if($request->btnNext) {
            return redirect("languagepack/tiles/{$languagePackSaved->id}");    
        }

        return redirect("languagepack/edit/{$languagePackSaved->id}");    
    }    

    private function getSettings(bool $create): array
    {
        $settings = [];
        $i = 0;
        foreach(LangInfoEnum::cases() as $setting) {
            if(old('setting')) {
                $settingValue = old('setting');
            } else {
                $settingValue = '';
                if(!$create) {
                    $langSetting = LanguageSetting::where('name', $setting->value)->first();
                    $settingValue = $langSetting ? $langSetting['value'] : '';
                }
            }   
            $settings[$i]['label'] = $setting->label();
            $settings[$i]['name'] = $setting->value;
            $settings[$i]['value'] = $settingValue;
            $settings[$i]['type'] = $setting->type();
            $settings[$i]['options'] = $setting->options();
            $i++;
        }    
        
        return $settings;
    }

    private function saveSettings(LanguagePack $languagePack, StoreLangPackRequest $request): void
    {
        LanguageSetting::where('languagepackid', $languagePack->id)
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
            
            LanguageSetting::insert($settings);
        }
    }
}
