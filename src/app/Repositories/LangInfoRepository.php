<?php
namespace App\Repositories;

use App\Enums\LangInfoEnum;
use App\Models\LanguagePack;
use App\Models\LanguageSetting;

class LangInfoRepository
{
    public function getSettings(bool $create, LanguagePack $languagePack = null): array
    {
        $settings = [];
        $i = 0;
        foreach(LangInfoEnum::cases() as $setting) {
            if(old('setting')) {
                $settingValue = old('setting');
            } else {
                $settingValue = '';
                if(!$create) {
                    $langSetting = LanguageSetting::where('languagepackid', $languagePack->id)
                    ->where('name', $setting->value)->first();
                    $settingValue = $langSetting ? $langSetting['value'] : '';
                }
            }   
            $settings[$i]['label'] = $setting->label();
            $settings[$i]['name'] = $setting->value;
            $settings[$i]['value'] = $settingValue;
            $settings[$i]['type'] = $setting->type();
            $settings[$i]['options'] = $setting->options();
            $settings[$i]['export_key'] = $setting->exportKey();
            $i++;
        }    
        
        return $settings;
    }
}