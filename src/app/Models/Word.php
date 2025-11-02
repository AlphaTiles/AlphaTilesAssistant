<?php

namespace App\Models;

use App\Enums\FileTypeEnum;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\HasConfigurableOrder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Word extends Model
{
    use SoftDeletes;
    use HasFactory;
    use HasConfigurableOrder;
    
    protected $fillable = [
        'languagepackid',
        'value',
        'mixed_types',
        'stage',
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
