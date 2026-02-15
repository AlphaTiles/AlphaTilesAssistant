<?php

namespace App\Models;

use App\Enums\ImportStatus;
use App\Enums\LangInfoEnum;
use App\Models\Key;
use App\Models\LanguageSetting;
use App\Models\Tile;
use App\Models\Word;
use App\Repositories\GameSettingsRepository;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LanguagePack extends Model
{    
    use SoftDeletes;
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'import_status'
    ];

    public function casts()
    {
        return [
            'import_status' => ImportStatus::class, 
        ];
    }    

    public function getAppIdAttribute(): ?string
    {
        $gameSettingsRepositoryClass = GameSettingsRepository::class;

        return app($gameSettingsRepositoryClass)->getAppId($this->id);
    }

    public function collaborators(): HasMany
    {
        return $this->hasMany(Collaborator::class, 'languagepack_id');
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
        return $this->belongsTo(User::class, 'user_id');
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

    public function resources(): HasMany
    {
        return $this->hasMany(Resource::class, 'languagepackid');
    }        

    public function gameSettings(): HasMany
    {
        return $this->hasMany(GameSetting::class, 'languagepackid');
    }  
}
