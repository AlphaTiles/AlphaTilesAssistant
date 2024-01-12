<?php

namespace App\Enums;

enum TileTypeEnum: string
{
    case CONSONANT        =     'C';
    case VOWEL            =     'V';
    case TONE_MARKER      =     'T';
    case SPACE_AND_DASH  =     'SAD';
    case OTHER            =     'X';
 
    public function label(): string
    {
        return match ($this) {
            self::CONSONANT => 'consonant',
            self::VOWEL     => 'vowel',
            self::TONE_MARKER   => 'tone diacritic',
            self::SPACE_AND_DASH => 'space and dash',
            self::OTHER     => 'other',
        };
    }
}