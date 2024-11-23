<?php
namespace App\Repositories;

use App\Models\GameSetting;
use App\Models\LanguagePack;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class BaseSettingsRepository
{
    protected string $enumClass;
    protected string $model;
    
    public function getSettings(bool $create, LanguagePack $languagePack = null): array
    {
        $settings = [];
        $i = 0;
        foreach($this->enumClass::cases() as $setting) {
            if(old('settings.' . $setting->value)) {
                $settingValue = old('settings.' . $setting->value);
            } else {
                $settingValue = $setting->defaultValue();
                if(!$create) {
                    $gameSetting = $this->model::where('languagepackid', $languagePack->id)
                    ->where('name', $setting->value)->first();
                    $settingValue = !empty($gameSetting) ? $gameSetting['value'] : $setting->defaultValue();
                }
            }   
            $settings[$i]['label'] = $setting->label();
            $settings[$i]['name'] = $setting->value;
            $settings[$i]['placeholder'] = $setting->defaultValue();
            $settings[$i]['value'] = $settingValue;
            $settings[$i]['type'] = $setting->type();
            $settings[$i]['options'] = $setting->options();
            $settings[$i]['export_key'] = $setting->exportKey();
            $settings[$i]['help_image'] = $setting->helpImage();
            $settings[$i]['help_text'] = $setting->helpText();
            $settings[$i]['max'] = $setting->max();
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