<?php

namespace App\Enums;

enum TileTypeEnum: string
{
    case CONSONANT        =     'C';
    case VOWEL            =     'V';
    case TONE_MARKER      =     'T';
    case OTHER            =     'X';
 
    public function label(): string
    {
        return match ($this) {
            self::CONSONANT => 'consonant',
            self::VOWEL     => 'vowel',
            self::TONE_MARKER   => 'tone marker',
            self::OTHER     => 'other',
        };
    }
}