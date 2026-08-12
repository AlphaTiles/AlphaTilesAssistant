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
            self::CONSONANT => __('consonant'),
            self::VOWEL     => __('vowel'),
            self::TONE_MARKER   => __('tone diacritic'),
            self::SPACE_AND_DASH => __('space and dash'),
            self::OTHER     => __('other'),
            self::LEADING_VOWEL => __('leading vowel'),
            self::BELOW_VOWEL => __('below vowel'), 
            self::ABOVE_VOWEL => __('above vowel'),
            self::FOLLOWING_VOWEL => __('following vowel'),
            self::ABOVE_DIACRITIC => __('above diacritic'),
            self::DIACRITIC => __('diacritic'),
        };
    }
}