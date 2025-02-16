<?php

namespace App\Enums;

enum TabEnum: string
{
    case KEY        = 'key';
    case SYLLABLE   = 'syllable';
    case TILE       = 'tile';
    case WORD       = 'word';

    public function name(): string
    {
        return match($this) {
            self::KEY => 'key',
            self::SYLLABLE => 'syllable',
            self::TILE => 'tile',
            self::WORD => 'word',            
        };
    }

    public function path(): string
    {
        return match($this) {
            self::KEY => 'keyboard',
            self::SYLLABLE => 'syllables',
            self::TILE => 'tiles',
            self::WORD => 'wordlist',            
        };
    }

}