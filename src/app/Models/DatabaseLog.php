<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DatabaseLog extends Model
{
    use HasFactory;

    protected $table = 'logs';

    protected $fillable = [
        'languagepackid',
        'message',
        'type',
        'status',
    ];
}
