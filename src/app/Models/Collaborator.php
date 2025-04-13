<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Collaborator extends Model
{
    protected $fillable = [
        'languagepack_id',
        'user_id',
    ];

    /**
     * Get the language pack that this collaborator belongs to.
     */
    public function languagePack(): BelongsTo
    {
        return $this->belongsTo(LanguagePack::class, 'languagepack_id');
    }

    /**
     * Get the user that this collaborator belongs to.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}