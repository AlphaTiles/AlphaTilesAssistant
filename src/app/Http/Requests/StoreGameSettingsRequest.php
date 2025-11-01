<?php

namespace App\Http\Requests;

use App\Enums\FieldTypeEnum;
use App\Enums\GameSettingEnum;
use App\Rules\GoogleServicesJson;
use Illuminate\Foundation\Http\FormRequest;

class StoreGameSettingsRequest extends FormRequest
{
    public function rules()
    {
        foreach(GameSettingEnum::cases() as $gameSetting) {
            $required = 'sometimes';
            if($gameSetting->type() != FieldTypeEnum::CHECKBOX && $gameSetting->value != GameSettingEnum::GOOGLE_SERVICES_JSON->value) {
                 $required = 'required'; 
            }   
            $requiredSettings['settings.' . $gameSetting->value] = $required;         
        }
        
        return [
            'id' => 'sometimes',
            'settings' => [
                'array',
            ],
            'settings.share_link' => 'sometimes',
            'settings.google_services_json' => ['sometimes', 'file', 'max:256', new GoogleServicesJson],
            'btnNext' => 'sometimes',
        ] + $requiredSettings;        
    }

    public function attributes()
    {
        foreach(GameSettingEnum::cases() as $gameSetting) {
            $attributes['settings.' . $gameSetting->value] = $gameSetting->label(); 
        }

        return $attributes;
    }    
}
