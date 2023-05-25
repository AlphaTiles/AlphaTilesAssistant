<?php

namespace App\Enums;

enum FieldTypeEnum: string
{
    case DROPDOWN       = 'dropdown';
    case INPUT          = 'input';
    case TEXTBOX         = 'textbox';
}