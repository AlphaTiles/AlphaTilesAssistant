<?php
namespace App\Repositories;

use App\Models\GameSetting;
use App\Enums\GameSettingEnum;

class GameSettingsRepository extends BaseSettingsRepository
{
    public function __construct()
    {
        $this->enumClass = GameSettingEnum::class;
        $this->model = GameSetting::class;
    }

    public function getAppId(?int $languagePackId): ?string
    {
        return GameSetting::where('languagepackid', $languagePackId)
            ->where('name', GameSettingEnum::APP_ID->value)
            ->first()
            ->value ?? null;
    }
}