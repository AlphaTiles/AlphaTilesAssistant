<?php

namespace App\Enums;

enum ExportStatus: string
{
    case IN_PROGRESS     = 'in_progress';
    case FAILED          = 'failed';
    case SUCCESS         = 'success';
}