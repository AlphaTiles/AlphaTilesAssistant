<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GameSetting extends Model
{
    use SoftDeletes;
    
    protected $fillable = [
        'languagepackid',
        'name',
        'value',
    ];
}
