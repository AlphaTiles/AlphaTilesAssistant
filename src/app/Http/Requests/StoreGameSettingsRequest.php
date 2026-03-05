<?php

namespace App\Http\Requests;

use App\Enums\FieldTypeEnum;
use App\Enums\GameSettingEnum;
use App\Rules\GoogleServicesJson;
use App\Rules\ValidAppId;
use Illuminate\Foundation\Http\FormRequest;

class StoreGameSettingsRequest extends FormRequest
{
    public function rules()
    {
        $requiredSettings = [];
        $hasGoogleServicesUpload = $this->hasFile('settings.' . GameSettingEnum::GOOGLE_SERVICES_JSON->value);

        foreach(GameSettingEnum::cases() as $gameSetting) {
            $required = 'sometimes';
            if($gameSetting->type() != FieldTypeEnum::CHECKBOX && $gameSetting->value != GameSettingEnum::GOOGLE_SERVICES_JSON->value) {
                 $required = 'required'; 
            }

            // App ID is extracted from uploaded google-services.json, so do not require it on upload requests.
            if ($hasGoogleServicesUpload && $gameSetting->value === GameSettingEnum::APP_ID->value) {
                $required = 'sometimes';
            }

            $requiredSettings['settings.' . $gameSetting->value] = $required;
        }

        $appIdRules = ['sometimes', 'nullable', 'string'];
        if (!$hasGoogleServicesUpload) {
            $appIdRules[] = new ValidAppId;
        }

        return [
            'id' => 'sometimes',
            'settings' => [
                'array',
            ],
            'settings.share_link' => 'sometimes',
            'settings.google_services_json' => ['sometimes', 'file', 'max:256', new GoogleServicesJson],
            'settings.app_id' => $appIdRules,
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
