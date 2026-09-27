<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DriveExport extends Model
{
    use HasFactory;

    protected $fillable = [
        'languagepackid',
        'folder_id',
        'folder_name',
        'drive_url',
        'is_shared',
    ];

    protected $casts = [
        'is_shared' => 'boolean',
    ];
}
