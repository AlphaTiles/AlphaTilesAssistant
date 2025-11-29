<?php

namespace App\Enums;

enum CountryEnum: string
{
    case Brazil = 'Brazil';
    case Chile = 'Chile';
    case China = 'China';
    case Colombia = 'Colombia';
    case Ecuador = 'Ecuador';
    case Georgia = 'Georgia';
    case Iraq = 'Iraq';
    case Italy = 'Italy';
    case Japan = 'Japan';
    case Malaysia = 'Malaysia';
    case Mexico = 'Mexico';
    case Myanmar = 'Myanmar';
    case Peru = 'Peru';
    case Romania = 'Romania';
    case Sudan = 'Sudan';
    case Thailand = 'Thailand';
    case UnitedStates = 'UnitedStates';

    public static function options(): array
    {
        return array_map(fn($c) => $c->value, self::cases());
    }
}
