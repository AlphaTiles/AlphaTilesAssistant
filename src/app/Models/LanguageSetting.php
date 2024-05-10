<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LanguageSetting extends Model
{
    use SoftDeletes;
    
    protected $fillable = [
        'languagepackid',
        'name',
        'value',
    ];
}
