<?php

namespace App\Enums;

use ReflectionClass;

enum ColorEnum: int
{
    case THEME_PURPLE        =  0;
    case THEME_BLUE          = 1;
    case THEME_ORANGE        = 2;
    case THEME_GREEN        = 3;
    case THEME_RED          = 4;
    case YELLOW           = 5;
    case BLACK              = 6;
    case DARK_GREEN       = 7;
    case GRAY          = 8;
    case BROWN    = 9;
    case RED            = 10;
    case MAGENTA                  = 11;    
    case BLUE         = 12;   

    public function hexCode(): string
    {
        return match ($this) {
            self::THEME_PURPLE      => '#9C27B0',
            self::THEME_BLUE             => '#2196F3',
            self::THEME_ORANGE    => '#F44336',
            self::THEME_GREEN   => '#4CAF50',
            self::THEME_RED => '#E91E63',
            self::YELLOW => '#FFFF00',
            self::BLACK => '#000000',
            self::DARK_GREEN => '#006600',
            self::GRAY => '#808080',
            self::BROWN => '#663300',
            self::RED => '#FF0000',
            self::MAGENTA => '#A50021',
            self::BLUE => '#0000CC',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::THEME_PURPLE      => 'theme purple',
            self::THEME_BLUE             => 'theme blue',
            self::THEME_ORANGE    => 'theme orange',
            self::THEME_GREEN   => 'theme green',
            self::THEME_RED => 'theme red',
            self::YELLOW => 'yellow',
            self::BLACK => 'black',
            self::DARK_GREEN => 'dark green',
            self::GRAY => 'gray',
            self::BROWN => 'brown',
            self::RED => 'red',
            self::MAGENTA => 'magenta',
            self::BLUE => 'blue',
        };        
    }
}