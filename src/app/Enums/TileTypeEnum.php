<?php

namespace App\Enums;

enum TileTypeEnum: string
{
    case CONSONANT        =     'C';
    case VOWEL            =     'V';
    case TONE_MARKER      =     'T';
    case SPACE_AND_DASH  =     'SAD';
    case OTHER            =     'X';
    case LEADING_VOWEL    =     'LV';
    case BELOW_VOWEL      =     'BV';
    case ABOVE_VOWEL      =     'AV';
    case FOLLOWING_VOWEL  =     'FV';
    case ABOVE_DIACRITIC  =     'AD';
    case DIACRITIC        =     'D';
 
    public function label(): string
    {
        return match ($this) {
            self::CONSONANT => 'consonant',
            self::VOWEL     => 'vowel',
            self::TONE_MARKER   => 'tone diacritic',
            self::SPACE_AND_DASH => 'space and dash',
            self::OTHER     => 'other',
            self::LEADING_VOWEL => 'leading vowel',
            self::BELOW_VOWEL => 'below vowel', 
            self::ABOVE_VOWEL => 'above vowel',
            self::FOLLOWING_VOWEL => 'following vowel',
            self::ABOVE_DIACRITIC => 'above diacritic',
            self::DIACRITIC => 'diacritic',
        };
    }
}