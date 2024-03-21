<?php
namespace App\Repositories;

use App\Enums\LangInfoEnum;
use App\Models\LanguagePack;
use App\Models\LanguageSetting;
use Illuminate\Support\Facades\Log;

class LangInfoRepository
{
    public function getSettings(bool $create, LanguagePack $languagePack = null): array
    {
        $settings = [];
        $i = 0;
        foreach(LangInfoEnum::cases() as $setting) {
            if(old('settings.' . $setting->value)) {
                $settingValue = old('settings.' . $setting->value);
            } else {
                $settingValue = $setting->defaultValue();
                if(!$create) {
                    $langSetting = LanguageSetting::where('languagepackid', $languagePack->id)
                    ->where('name', $setting->value)->first();
                    $settingValue = !empty($langSetting) ? $langSetting['value'] : $setting->defaultValue();
                }
            }   
            $settings[$i]['label'] = $setting->label();
            $settings[$i]['name'] = $setting->value;
            $settings[$i]['placeholder'] = $setting->defaultValue();
            $settings[$i]['value'] = $settingValue;
            $settings[$i]['type'] = $setting->type();
            $settings[$i]['options'] = $setting->options();
            $settings[$i]['export_key'] = $setting->exportKey();
            $i++;
        }    
        
        return $settings;
    }

    static function getValue($errors, array $setting)
    {	
        if (isset($errors) && $errors->any()) {
            return old('settings.' . $setting['name']) ?? '';
        }
    
        return $setting['value'];
    }
    
}