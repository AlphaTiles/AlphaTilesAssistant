<?php

namespace App\Enums;

use App\Services\ValidationService;

enum ErrorTypeEnum: string
{
    case NO_KEYBOARD_KEYS            = 'no_keyboard_keys';
    case MISSING_WORD_AUDIO_FILE       = 'missing_word_audio_file';
    case MISSING_WORD_IMAGE_FILE       = 'missing_word_image_file';
    case MISSING_SYLLABLE_AUDIO_FILE       = 'missing_syllable_audio_file';
    case MISSING_TILE_AUDIO_FILE       = 'missing_tile_audio_file';
    case COLOR_KEY                    = 'color_key';
    case DUPLICATE_KEY                 = 'duplicate_key';
    case DUPLICATE_SYLLABLE            = 'duplicate_syllable';
    case DUPLICATE_TILE                = 'duplicate_tile';
    case DUPLICATE_WORD                = 'duplicate_word';
    case EMPTY_DISTRACTOR_SYLLABLE      = 'empty_distractor_syllable';
    case EMPTY_DISTRACTOR_TILE         = 'empty_distractor_tile';
    case EMPTY_TYPE_TILE              = 'empty_type_tile';
    case KEY_NOT_USED_IN_WORDS        = 'key_not_used_in_words';
    case TILE_USAGE                    = 'tile_usage';
    case PARSE_WORD_INTO_TILES         = 'parse_word_into_tiles';
    case PARSE_WORD_INTO_KEYS          = 'parse_word_into_keys';
    case MISSING_APP_ID                 = 'missing_app_id';

    public function label(): string
    {
        return match($this) {
            self::NO_KEYBOARD_KEYS => 'No keyboard keys found',
            self::MISSING_WORD_AUDIO_FILE => 'An audio file is required',
            self::MISSING_WORD_IMAGE_FILE => 'An image file is required',
            self::MISSING_SYLLABLE_AUDIO_FILE => 'An audio file is required',
            self::MISSING_TILE_AUDIO_FILE => 'An audio file is required',
            self::COLOR_KEY                 => 'The following keys are not color coded',
            self::DUPLICATE_KEY          => 'The following keys are duplicate',
            self::DUPLICATE_SYLLABLE     => 'The following syllables are duplicate',
            self::DUPLICATE_TILE          => 'The following tiles are duplicate',
            self::DUPLICATE_WORD           => 'The following words are duplicate',
            self::EMPTY_DISTRACTOR_SYLLABLE => 'Distractors cannot be empty',
            self::EMPTY_DISTRACTOR_TILE         => 'Distractors cannot be empty',
            self::EMPTY_TYPE_TILE         => 'Type cannot be empty',
            self::KEY_NOT_USED_IN_WORDS   => 'The following keys are not used in any words',
            self::TILE_USAGE               => "It is recommended that each tile be used at least " . ValidationService::NUM_TIMES_TILES_WANTED_IN_WORDS . " times",
            self::PARSE_WORD_INTO_TILES   => "Words could not be parsed into tiles",
            self::PARSE_WORD_INTO_KEYS    => "Words could not be parsed into keys",
            self::MISSING_APP_ID          => 'The App ID is missing from the game settings. Please upload a valid google-services.json file under "Game Settings" to extract the App ID.',
        };
    }

    public function tab(): TabEnum
    {
        return match($this) {
            self::NO_KEYBOARD_KEYS => TabEnum::KEY,
            self::MISSING_WORD_AUDIO_FILE => TabEnum::WORD,
            self::MISSING_WORD_IMAGE_FILE => TabEnum::WORD,
            self::MISSING_SYLLABLE_AUDIO_FILE => TabEnum::SYLLABLE,
            self::MISSING_TILE_AUDIO_FILE => TabEnum::TILE,
            self::COLOR_KEY => TabEnum::KEY,
            self::DUPLICATE_KEY => TabEnum::KEY,
            self::DUPLICATE_SYLLABLE => TabEnum::SYLLABLE,
            self::DUPLICATE_TILE => TabEnum::TILE,
            self::DUPLICATE_WORD => TabEnum::WORD,
            self::EMPTY_DISTRACTOR_SYLLABLE => TabEnum::SYLLABLE,
            self::EMPTY_DISTRACTOR_TILE => TabEnum::TILE,
            self::EMPTY_TYPE_TILE => TabEnum::TILE,
            self::KEY_NOT_USED_IN_WORDS => TabEnum::KEY,
            self::TILE_USAGE => TabEnum::TILE,
            self::PARSE_WORD_INTO_TILES => TabEnum::WORD,
            self::PARSE_WORD_INTO_KEYS => TabEnum::WORD,
            self::MISSING_APP_ID => TabEnum::GAME_SETTINGS,
        };
    }

    public function isLinkable(): bool
    {
        return match($this) {
            self::NO_KEYBOARD_KEYS => false,
            self::KEY_NOT_USED_IN_WORDS => false,
            self::TILE_USAGE => false,
            self::PARSE_WORD_INTO_TILES => false,
            self::PARSE_WORD_INTO_KEYS => false,
            default => true,
        };
    }

}
