<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
}
