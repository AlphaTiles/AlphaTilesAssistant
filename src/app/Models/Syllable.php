<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Syllable extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'languagepackid',
        'value',
        'file_id',
        'or_1',
        'or_2',
        'or_3',
        'color'
    ];

    public function file(): HasOne
    {
        return $this->hasOne(File::class, 'id', 'file_id');
    }
}
