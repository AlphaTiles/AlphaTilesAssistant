<?php

namespace App\Models;

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
        'country',
        'level',
        'color',
        'audiofile_id',
        'audio_duration',
        'syll_or_tile',
        'stages_included',
        'friendly_name',
    ];
}
