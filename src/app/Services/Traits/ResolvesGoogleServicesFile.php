<?php

namespace App\Services\Traits;

use App\Models\File;
use App\Models\GameSetting;
use App\Enums\GameSettingEnum;

trait ResolvesGoogleServicesFile
{
    protected function getGoogleServicesFileRecord(): ?File
    {
        $googleServicesSetting = GameSetting::where('languagepackid', $this->languagePack->id)
            ->where('name', GameSettingEnum::GOOGLE_SERVICES_JSON->value)
            ->first();

        if (empty($googleServicesSetting?->value)) {
            return null;
        }

        return File::find((int) $googleServicesSetting->value);
    }

    protected function getGoogleServicesRelativePath(): ?string
    {
        $file = $this->getGoogleServicesFileRecord();
        if (!$file) {
            return null;
        }

        return ltrim(str_replace('/storage/', '', $file->file_path), '/');
    }

    protected function getGoogleServicesAbsolutePath(): ?string
    {
        $relativePath = $this->getGoogleServicesRelativePath();
        if (!$relativePath) {
            return null;
        }

        return storage_path('app/public/' . $relativePath);
    }
}