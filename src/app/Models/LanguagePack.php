<?php

namespace App\Models;

use App\Enums\LangInfoEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LanguagePack extends Model
{
    use SoftDeletes;
    
    protected $fillable = [
        'userid',
        'name',
    ];

    public function hasSetting(LangInfoEnum $langInfo): bool
    {
        return $this->settings->pluck('name')->contains($langInfo->value);
    }

    public function returnSetting(LangInfoEnum $langInfo): string
    {
        $testSetting = $this->settings->where('name', $langInfo->value)->first();

        return isset($testSetting) ? $testSetting['value'] : '';
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'userid');
    }

    public function settings(): HasMany
    {
        return $this->hasMany(LanguageSetting::class, 'testid');
    }
}
