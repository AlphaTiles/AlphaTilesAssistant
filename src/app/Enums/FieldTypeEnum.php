<?php

namespace App\Enums;

enum FieldTypeEnum: string
{
    case CHECKBOX       = 'checkbox';
    case DROPDOWN       = 'dropdown';
    case INPUT          = 'input';
    case TEXTBOX        = 'textbox';    
    case NUMBER         = 'number';
    case UPLOAD         = 'upload';
    case LABEL          = 'label';
}