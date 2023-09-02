<?php

namespace App\Enums;

enum TileTypeEnum: string
{
    case CONSONENT        =     'C';
    case VOWEL            =     'V';
    case OTHER            =     'X';
 
    public function label(): string
    {
        return match ($this) {
            self::CONSONENT => 'consonent',
            self::VOWEL     => 'vowel',
            self::OTHER     => 'other',
        };
    }
}