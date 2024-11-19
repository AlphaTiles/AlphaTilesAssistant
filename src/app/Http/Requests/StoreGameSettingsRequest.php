<?php

namespace App\Http\Requests;

use App\Enums\FieldTypeEnum;
use App\Enums\GameSettingEnum;
use Illuminate\Foundation\Http\FormRequest;

class StoreGameSettingsRequest extends FormRequest
{
    public function rules()
    {
        foreach(GameSettingEnum::cases() as $gameSetting) {
            $required = 'sometimes';
            if($gameSetting->type() != FieldTypeEnum::CHECKBOX) {
                 $required = 'required'; 
            }   
            $requiredSettings['settings.' . $gameSetting->value] = $required;         
        }
        
        return [
            'id' => 'sometimes',
            'settings' => [
                'array',
            ],
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
