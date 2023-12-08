<?php

namespace App\Models;

use App\Enums\FileTypeEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Word extends Model
{
    protected $fillable = [
        'languagepackid',
        'value',
        'translation',
        'mixed_types',
    ];

    public function audioFile(): HasOne
    {
        return $this->hasOne(File::class, 'id', 'audiofile_id')
            ->where('type', FileTypeEnum::AUDIO->value);
    }

    public function imageFile(): HasOne
    {
        return $this->hasOne(File::class, 'id', 'imagefile_id')
            ->where('type', FileTypeEnum::IMAGE->value);
    }    
}
