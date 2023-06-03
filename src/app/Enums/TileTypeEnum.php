<?php

namespace App\Enums;

enum TileTypeEnum: string
{
    case CONSONENT        =     'C';
    case VOWEL            =     'V';
 
    public function label(): string
    {
        return match ($this) {
            self::CONSONENT => 'consonent',
            self::VOWEL     => 'vowel',
        };
    }
}