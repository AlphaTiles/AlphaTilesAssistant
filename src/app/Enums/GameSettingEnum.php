<?php

namespace App\Enums;

use Google\Service\CloudHealthcare\Field;

enum GameSettingEnum: string
{
    case SCAN_SETTING        = 'scan_setting';
    case HAS_TILE_AUDIO      = 'has_tile_audio';
    case AFTER_12_TRACKERS = 'after_12_trackers';
    case SYLLABLE_AUDIO = 'syllable_audio';
    case NUMBER_AVATARS = 'number_avatars';
    case DIFFERENTIATE_TYPES = 'differentiate_types';
    case STAGE_CORRESPONDENCE = 'stage_correspondence';
    case FIRST_LETTER_CORRESPONDENCE = 'first_letter_correspondence';
    case WORD_LENGTH = 'word_length';
    case DAYS_EXPIRATION = 'days_expiration';
    case CHILE_KEYBOARD_WIDTH = 'chile_keyboard_width';
    case CHILE_GUESS_COUNT = 'chile_guess_count';
    case CHILE_MIN_WORD_LENGTH = 'chile_min_word_length';
    case CHILE_MAX_WORD_LENGTH = 'chile_max_word_length';
    case BOLD_NON_INITIAL_TILES = 'bold_non_initial_tiles';
    case BOLD_INITIAL_TILES = 'bold_initial_tiles';
    case SHARE_LINK = 'share_link';
    case GOOGLE_SERVICES_JSON = 'google_services_json';

    public function defaultValue(): string
    {
        return match ($this) {
            self::SCAN_SETTING      => '1',
            self::HAS_TILE_AUDIO    => false,    
            self::AFTER_12_TRACKERS => '3',       
            self::SYLLABLE_AUDIO => false, 
            self::NUMBER_AVATARS => 12,
            self::DIFFERENTIATE_TYPES => false,
            self::STAGE_CORRESPONDENCE => 1,
            self::FIRST_LETTER_CORRESPONDENCE => false,
            self::WORD_LENGTH => 99,
            self::DAYS_EXPIRATION => 9999,
            self::CHILE_KEYBOARD_WIDTH => 7,
            self::CHILE_GUESS_COUNT => 7,
            self::CHILE_MIN_WORD_LENGTH => 5,
            self::CHILE_MAX_WORD_LENGTH => 5,
            self::BOLD_NON_INITIAL_TILES => false,
            self::BOLD_INITIAL_TILES => false,
            self::GOOGLE_SERVICES_JSON => '',
            default                 => '',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::SCAN_SETTING       => 'Game 001 Scan Setting',
            self::HAS_TILE_AUDIO    =>  'Has tile audio',
            self::AFTER_12_TRACKERS => 'After 12 checked trackers',
            self::SYLLABLE_AUDIO => 'Has syllable audio',            
            self::NUMBER_AVATARS => 'Number of avatars',
            self::DIFFERENTIATE_TYPES => 'Differentiates types of multitype symbols',
            self::STAGE_CORRESPONDENCE => 'Stage correspondence ratio',
            self::FIRST_LETTER_CORRESPONDENCE => 'First letter stage correspondence',
            self::WORD_LENGTH => 'Stage 1-2 max word length',
            self::DAYS_EXPIRATION => 'Days until expiration',
            self::CHILE_KEYBOARD_WIDTH => 'Chile keyboard width',
            self::CHILE_GUESS_COUNT => 'Chile base guess count',
            self::CHILE_MIN_WORD_LENGTH => 'Chile minimum word length',
            self::CHILE_MAX_WORD_LENGTH => 'Chile maximum word length',
            self::BOLD_NON_INITIAL_TILES => 'Game 001 bold non-initial tiles',
            self::BOLD_INITIAL_TILES => 'Game 001 bold initial tiles',
            self::SHARE_LINK => 'Share link',
            self::GOOGLE_SERVICES_JSON => 'Google Services JSON',
        };
    }

    public function helpImage(): ?string
    {
        return match($this) {
            self::SCAN_SETTING => '/images/help/scan_settings.png',
            self::AFTER_12_TRACKERS => '/images/help/after_12_trackers.png',
            self::NUMBER_AVATARS => '/images/help/number_avatars.png',
            self::DIFFERENTIATE_TYPES => '/images/help/differentiate_types.png',
            self::STAGE_CORRESPONDENCE => '/images/help/stage_correspondence.png',
            self::FIRST_LETTER_CORRESPONDENCE => '/images/help/first_letter_correspondence.png',
            default => null
        };
    }

    public function helpText(): ?string
    {
        return match($this) {
            self::WORD_LENGTH => 'Maximum word length of stages 1 and 2 in terms of tiles',
            self::DAYS_EXPIRATION => 'You may add an optional setting if you want your app to have an expiration date. For example, if you are testing your language’s orthography, you may want to distribute a temporary version of the app that expires after 30 days.',
            self::CHILE_KEYBOARD_WIDTH => 'For the Guess the Word (Chile.java) game, you can specify the keyboard width, the number of guesses per round and the range of word sizes (in tile length) allowed.',
            self::BOLD_NON_INITIAL_TILES => 'In Game 001 (Romania) bold non-initial tiles when in focus? (boldNonInitialFocusTiles)',
            self::BOLD_INITIAL_TILES => 'In Game 001 (Romania) bold initial tiles when in focus? (boldInitialFocusTiles)',
            self::SHARE_LINK => 'The share button allows you to point the user directly to the Play Store or another link. If link included, Share icon will appear at the bottom of Game selection screen. Clicking Share icon will display QR code.',
            default => null
        };
    }

    public function exportKey(): string
    {
        return match ($this) {
            self::SCAN_SETTING       => '1. Game 001 Scan Setting',
            self::HAS_TILE_AUDIO     => '2. Has tile audio',
            self::AFTER_12_TRACKERS => '3. After 12 checked trackers',
            self::SYLLABLE_AUDIO => '4. Has syllable audio',
            self::NUMBER_AVATARS => '5. Number of avatars',
            self::DIFFERENTIATE_TYPES => '6. Differentiates types of multitype symbols',
            self::STAGE_CORRESPONDENCE => '7. Stage correspondence ratio',
            self::FIRST_LETTER_CORRESPONDENCE => '8. First letter stage correspondence',
            self::WORD_LENGTH => '9. Stage 1-2 max word length',
            self::DAYS_EXPIRATION => '10. Days until expiration',
            self::CHILE_KEYBOARD_WIDTH => '11. Chile keyboard width',
            self::CHILE_GUESS_COUNT => '12. Chile base guess count',
            self::CHILE_MIN_WORD_LENGTH => '13. Chile minimum word length',
            self::CHILE_MAX_WORD_LENGTH => '14. Chile maximum word length',
            self::BOLD_NON_INITIAL_TILES => '15. In Game 001 (Romania) bold non-initial tiles when in focus? (boldNonInitialFocusTiles)',
            self::BOLD_INITIAL_TILES => '16. In Game 001 (Romania) bold initial tiles when in focus? (boldInitialFocusTiles)',            
            self::GOOGLE_SERVICES_JSON => '',
            self::SHARE_LINK => 'aa_share.txt',
        };
    }

    public function type(): FieldTypeEnum
    {
        return match($this) {
            self::SCAN_SETTING  => FieldTypeEnum::DROPDOWN,
            self::HAS_TILE_AUDIO => FieldTypeEnum::CHECKBOX,
            self::AFTER_12_TRACKERS => FieldTypeEnum::DROPDOWN,
            self::SYLLABLE_AUDIO => FieldTypeEnum::CHECKBOX,
            self::NUMBER_AVATARS => FieldTypeEnum::NUMBER,
            self::DIFFERENTIATE_TYPES => FieldTypeEnum::CHECKBOX,
            self::STAGE_CORRESPONDENCE => FieldTypeEnum::DROPDOWN,
            self::FIRST_LETTER_CORRESPONDENCE => FieldTypeEnum::CHECKBOX,
            self::WORD_LENGTH => FieldTypeEnum::NUMBER,
            self::DAYS_EXPIRATION => FieldTypeEnum::NUMBER,
            self::CHILE_KEYBOARD_WIDTH => FieldTypeEnum::NUMBER,
            self::CHILE_GUESS_COUNT => FieldTypeEnum::NUMBER,
            self::CHILE_MIN_WORD_LENGTH => FieldTypeEnum::NUMBER,
            self::CHILE_MAX_WORD_LENGTH => FieldTypeEnum::NUMBER,
            self::BOLD_NON_INITIAL_TILES => FieldTypeEnum::CHECKBOX,
            self::BOLD_INITIAL_TILES => FieldTypeEnum::CHECKBOX,
            self::GOOGLE_SERVICES_JSON => FieldTypeEnum::UPLOAD,
            default                 => FieldTypeEnum::INPUT
        };
    }

    public function options(): ?array
    {
        return match($this) {
            self::SCAN_SETTING       => [
                                            '1' => '1 - only tile-initial words', 
                                            '2' => '2 - tile-initial words prioritised',
                                            '3' => '3 - both initial and non-initial words',
                                       ],
            self::AFTER_12_TRACKERS => [
                                            '1' => '1 - nothing happens', 
                                            '2' => '2 - app returns to game',
                                            '3' => '3 - app moves to next incomplete game',
                                        ],
            self::STAGE_CORRESPONDENCE => [
                                '0.1' => '10%',
                                '0.2' => '20%',
                                '0.3' => '30%',
                                '0.4' => '40%',
                                '0.5' => '50%',
                                '0.6' => '60%',
                                '0.7' => '70%',
                                '0.8' => '80%',
                                '0.9' => '90%',
                                '1' => '100%',
            ],
            default                 => null
        };
    }

    public function max(): ?int
    {
        return match($this) {
            self::NUMBER_AVATARS       => 12,
            default                 => null
        };
    }    
}