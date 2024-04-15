<?php

namespace App\Enums;

enum ImportStatus: string
{
    case IMPORTING       = 'importing';
    case FAILED          = 'failed';
    case SUCCESS         = 'success';
}