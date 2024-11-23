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
    case SCRIPT_TYPE            = 'script_type';
    case EMAIL                  = 'email';    
    case PRIVACY_POLICY         = 'privacy_policy';   
    case MEDIA_CREDITS2          = 'media_credits2'; 

    public function defaultValue(): string
    {
        return match ($this) {
            self::VARIANT_INFO      => '1',
            self::EMAIL             => 'alpha_tiles@sil.org',
            self::PRIVACY_POLICY    => 'https://alphatilesapps.org/privacypolicy.html',
            default                 => '',
        };
    }

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
            self::NAME_LOCAL_LANGUAGE   => 'Word NAME in local language',
            self::SCRIPT_TYPE           => 'Script type',
            self::EMAIL                 => 'Email',
            self::PRIVACY_POLICY        => 'Privacy Policy',
            self::MEDIA_CREDITS2        => 'Audio and image credits (lang 2)'
        };
    }

    public function helpImage(): ?string
    {
        return match($this) {
            default => null
        };
    }

    public function helpText(): ?string
    {
        return match($this) {
            default => null
        };
    }

    public function exportKey(): string
    {
        return match ($this) {
            self::LANG_NAME_LOCAL       => '1. Lang Name (In Local Lang)',
            self::LANG_NAME_ENGLISH     => '2. Lang Name (In English)',
            self::LANG_NAME_REGIONAL    => '3. Lang Name (In Reg/Ntnl Lang)',
            self::ETHNOLOGUE_CODE       => '4. Ethnologue code',
            self::COUNTRY               => '5. Country',
            self::VARIANT_INFO          => '6. Variant info',
            self::GAME_NAME             => '7. Game Name (In Local Lang)',
            self::SCRIPT_DIRECTION      => '8. Script direction (LTR or RTL)',
            self::MEDIA_CREDITS         => '9. Audio and image credits',
            self::NAME_LOCAL_LANGUAGE   => '10. The word NAME in local language',
            self::SCRIPT_TYPE           => '11. Script type',
            self::EMAIL                 => '12. Email',
            self::PRIVACY_POLICY        => '13. Privacy Policy',
            self::MEDIA_CREDITS2        => '14. Audio and image credits (lang 2)'
        };
    }

    public function type(): FieldTypeEnum
    {
        return match($this) {
            self::SCRIPT_DIRECTION  => FieldTypeEnum::DROPDOWN,
            self::SCRIPT_TYPE       => FieldTypeEnum::DROPDOWN,
            default                 => FieldTypeEnum::INPUT
        };
    }

    public function options(): ?array
    {
        return match($this) {
            self::SCRIPT_DIRECTION  => ['LTR' => 'LTR', 'RTL' => 'RTL'],
            self::SCRIPT_TYPE       => [
                                            'Roman' => 'Roman', 
                                            'Arabic' => 'Arabic',
                                            'Devanagari' => 'Devanagari',
                                            'Thai/Lao' => 'Thai/Lao',
                                            'Other' => 'Other'
                                       ],
            default                 => null
        };
    }

    public function max(): ?int
    {
        return match($this) {
            default                 => null
        };
    }    

}