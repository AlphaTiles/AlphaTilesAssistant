<?php

namespace App\Enums;

enum ErrorTypeEnum: string
{
    case MISSING_WORD_AUDIO_FILE       = 'missing_word_audio_file';
    case MISSING_WORD_IMAGE_FILE       = 'missing_word_image_file';
}