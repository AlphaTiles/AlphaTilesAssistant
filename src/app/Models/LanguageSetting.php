<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LanguageSetting extends Model
{
    protected $fillable = [
        'languagepackid',
        'name',
        'value',
    ];
}
