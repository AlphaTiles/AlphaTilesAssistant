<?php

namespace App\Models;

use App\Models\LanguagePack;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Tile extends Model
{
    protected $fillable = [
        'languagepackid',
        'value',
        'upper',
        'type',
        'stage',
        'or_1',
        'or_2',
        'or_3',
        'type2',
        'stage2',
        'type3',
        'stage3',
    ];

    public function file(): HasOne
    {
        return $this->hasOne(File::class, 'id', 'file_id');
    }

    public function file2(): HasOne
    {
        return $this->hasOne(File::class, 'id', 'file2_id');
    }

    public function file3(): HasOne
    {
        return $this->hasOne(File::class, 'id', 'file3_id');
    }
}
