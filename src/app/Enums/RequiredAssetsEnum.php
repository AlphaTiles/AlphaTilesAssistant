<?php

namespace App\Enums;

enum RequiredAssetsEnum: string
{
    case BASIC_ASSETS = 'Basic Assets';
    case TA = 'TA';
    case SB_T = 'SB/T';
    case SB_T_SA = 'SB/T+SA';

    public function label(): string
    {
        return match ($this) {
            self::BASIC_ASSETS => 'basic assets',
            self::TA => 'tile audio',
            self::SB_T => 'syllable breaks only',
            self::SB_T_SA => 'syllable breaks and syllable audio',
        };
    }

    public static function options(): array
    {
        return array_map(fn($item) => $item->value, self::cases());
    }
}
