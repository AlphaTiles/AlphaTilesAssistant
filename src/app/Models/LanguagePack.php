<?php

namespace App\Models;

use App\Enums\ImportStatus;
use App\Models\Key;
use App\Models\Tile;
use App\Models\Word;
use App\Enums\LangInfoEnum;
use App\Models\LanguageSetting;
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
        'import_status'
    ];

    public function casts()
    {
        return [
            'import_status' => ImportStatus::class, 
        ];
    }    

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

    public function langInfo(): HasMany
    {
        return $this->hasMany(LanguageSetting::class, 'languagepackid');
    }

    public function tiles(): HasMany
    {
        return $this->hasMany(Tile::class, 'languagepackid');
    }

    public function words(): HasMany
    {
        return $this->hasMany(Word::class, 'languagepackid');
    }

    public function keys(): HasMany
    {
        return $this->hasMany(Key::class, 'languagepackid');
    }

    public function syllables(): HasMany
    {
        return $this->hasMany(Syllable::class, 'languagepackid');
    }    
}
