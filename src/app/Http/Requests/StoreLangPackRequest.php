<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLangPackRequest extends FormRequest
{
    public function rules()
    {
        return [
            'id' => 'sometimes',
            'settings' => [
                'array'
            ],
            'btnNext' => 'sometimes',
            'settings.lang_name_local' => 'required',
            'settings.lang_name_english' => 'required',
        ];
    }

    public function attributes()
    {
        return [
            'settings.lang_name_local' => 'Language Name (Local)',
            'settings.lang_name_english' => 'Language Name (English)',
        ];
    }    
}
