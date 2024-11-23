<?php
namespace App\Repositories;

use App\Enums\LangInfoEnum;
use App\Models\LanguageSetting;

class LangInfoRepository extends BaseSettingsRepository
{
    public function __construct()
    {
        $this->enumClass = LangInfoEnum::class;
        $this->model = LanguageSetting::class;
    }
}