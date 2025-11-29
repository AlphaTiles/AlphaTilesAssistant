<?php

namespace App\Models;

use App\Enums\FileTypeEnum;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\HasConfigurableOrder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Game extends Model
{
    use SoftDeletes;
    use HasFactory;
    use HasConfigurableOrder;
    
    protected $fillable = [
        'include',
        'languagepackid',
        'door',
        'order',
        'country',
        'level',
        'color',
        'file_id',
        'audio_duration',
        'syll_or_tile',
        'stages_included',
        'friendly_name',
    ];

    public function file(): HasOne
    {
        return $this->hasOne(File::class, 'id', 'file_id');
    }
}
