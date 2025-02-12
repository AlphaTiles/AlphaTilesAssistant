<?php

namespace App\Enums;

enum TabEnum: string
{
    case TILE       = 'tile';
    case WORD       = 'word';

    public function name(): string
    {
        return match($this) {
            self::TILE => 'tile',
            self::WORD => 'word',            
        };
    }

    public function path(): string
    {
        return match($this) {
            self::TILE => 'tiles',
            self::WORD => 'wordlist',            
        };
    }

}