<?php

namespace App\Enums;

enum ErrorLevelEnum: string
{
    case CRITICAL = 'critical';
    case WARNING = 'warning';
    case RECOMMENDATION = 'recommendation';
}
