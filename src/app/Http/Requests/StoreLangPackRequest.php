<?php

namespace App\Http\Requests;

use App\Enums\LangInfoEnum;
use App\Rules\ThreeLettersRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreLangPackRequest extends FormRequest
{
    public function rules()
    {
        foreach(LangInfoEnum::cases() as $langInfo) {
            $requiredSettings['settings.' . $langInfo->value] = 'required'; 
        }
        
        return [
            'id' => 'sometimes',
            'settings' => [
                'array',
            ],
            'settings.ethnologue_code' => [new ThreeLettersRule],
            'settings.game_name' => ['required', 'max:20'],
            'settings.name_local_language' => ['required', 'max:12'],
            'btnNext' => 'sometimes',
        ] + $requiredSettings;        
    }

    public function attributes()
    {
        foreach(LangInfoEnum::cases() as $langInfo) {
            $attributes['settings.' . $langInfo->value] = $langInfo->label(); 
        }

        return $attributes;
    }    
}
