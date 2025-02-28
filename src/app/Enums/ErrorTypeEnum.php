<?php

namespace App\Enums;

use App\Services\ValidationService;

enum ErrorTypeEnum: string
{
    case MISSING_WORD_AUDIO_FILE       = 'missing_word_audio_file';
    case MISSING_WORD_IMAGE_FILE       = 'missing_word_image_file';
    case DUPLICATE_KEY                 = 'duplicate_key';
    case DUPLICATE_SYLLABLE            = 'duplicate_syllable';
    case DUPLICATE_TILE                = 'duplicate_tile';
    case DUPLICATE_WORD                = 'duplicate_word';
    case TILE_USAGE                    = 'tile_usage';

    public function label(): string
    {
        return match($this) {
            self::MISSING_WORD_AUDIO_FILE => 'An audio file is required',
            self::MISSING_WORD_IMAGE_FILE => 'An image file is required',
            self::DUPLICATE_KEY          => 'The following keys are duplicate',
            self::DUPLICATE_SYLLABLE     => 'The following syllables are duplicate',
            self::DUPLICATE_TILE          => 'The following tiles are duplicate',
            self::DUPLICATE_WORD           => 'The following words are duplicate',
            self::TILE_USAGE               => "It is recommended that each tile be used at least " . ValidationService::NUM_TIMES_TILES_WANTED_IN_WORDS . " times",
        };
    }

    public function tab(): TabEnum
    {
        return match($this) {
            self::MISSING_WORD_AUDIO_FILE => TabEnum::WORD,
            self::MISSING_WORD_IMAGE_FILE => TabEnum::WORD,
            self::DUPLICATE_KEY => TabEnum::KEY,
            self::DUPLICATE_SYLLABLE => TabEnum::SYLLABLE,
            self::DUPLICATE_TILE => TabEnum::TILE,
            self::DUPLICATE_WORD => TabEnum::WORD,
            self::TILE_USAGE => TabEnum::TILE,
        };
    }

    public function isLinkable(): bool
    {
        return match($this) {
            self::TILE_USAGE => false,
            default => true,
        };
    }

}
