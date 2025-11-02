<?php

namespace App\Models\Traits;

use App\Models\LanguagepackConfig;
use App\Models\LanguagePack;
use Illuminate\Database\Eloquent\Builder;

trait HasConfigurableOrder
{
    /**
     * Get the "order by" column from LanguagePackConfig.
     */
    public static function getOrderByValue(LanguagePack $languagePack, string $configKey, string $default = 'value'): string
    {
        return LanguagepackConfig::where('languagepackid', $languagePack->id)
            ->where('name', $configKey)
            ->value('value') ?? $default;
    }

    /**
     * Scope to apply order by column dynamically based on LanguagePackConfig.
     */
    public function scopeOrderByConfig(Builder $query, LanguagePack $languagePack, string $configKey, string $default = 'value'): Builder
    {
        $column = static::getOrderByValue($languagePack, $configKey, $default);

        return $query->orderBy($column);
    }
}
