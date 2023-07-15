<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Tile extends Model
{
    protected $fillable = [
        'languagepackid',
        'value',
        'upper',
        'type',
        'or_1',
        'or_2',
        'or_3'
    ];

    public function file(): HasOne
    {
        return $this->hasOne(File::class, 'id', 'file_id');
    }
}
