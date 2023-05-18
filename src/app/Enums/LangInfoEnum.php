<?php

namespace App\Enums;

enum LangInfoEnum: string
{
    case LANG_NAME_LOCAL        = 'lang_name_local';
    case LANG_NAME_ENGLISH      = 'lang_name_english';
    case LANG_NAME_REGIONAL     = 'lang_name_regional';
    case ETHNOLOGUE_CODE        = 'ethnologue_code';
    case COUNTRY                = 'country';
    case VARIANT_INFO           = 'variant_info';
    case GAME_NAME              = 'game_name';
    case SCRIPT_DIRECTION       = 'script_direction';
    case MEDIA_CREDITS          = 'media_credits';
    case NAME_LOCAL_LANGUAGE    = 'name_local_language';
 
    public function label(): string
    {
        return match ($this) {
            self::LANG_NAME_LOCAL       => 'Language Name (in local language)',
            self::LANG_NAME_ENGLISH     => 'Language Name (English)',
            self::LANG_NAME_REGIONAL    => 'Lang Name (in regional/national language)',
            self::ETHNOLOGUE_CODE       => 'Ethnologue code',
            self::COUNTRY               => 'Country',
            self::VARIANT_INFO          => 'Variant info',
            self::GAME_NAME             => 'Game Name (In Local Lang)',
            self::SCRIPT_DIRECTION      => 'Script direction',
            self::MEDIA_CREDITS         => 'Audio and image credits',
            self::NAME_LOCAL_LANGUAGE   => 'The word NAME in local language'
        };
    }

    public function type(): FieldTypeEnum
    {
        return match($this) {
            self::SCRIPT_DIRECTION  => FieldTypeEnum::DROPDOWN,
            self::MEDIA_CREDITS     => FieldTypeEnum::TEXTBOX,
            default                 => FieldTypeEnum::INPUT
        };
    }

    public function options(): ?array
    {
        return match($this) {
            self::SCRIPT_DIRECTION  => ['LTR' => 'LTR', 'RTL' => 'RTL'],
            default                 => null
        };
    }
}