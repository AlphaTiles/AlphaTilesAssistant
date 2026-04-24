<?php

namespace Tests\Unit;

use App\Enums\ErrorLevelEnum;
use App\Enums\ErrorTypeEnum;
use Tests\TestCase;

class ErrorTypeEnumTest extends TestCase
{
    public function test_error_types_have_expected_levels(): void
    {
        $this->assertSame(ErrorLevelEnum::CRITICAL, ErrorTypeEnum::MISSING_WORD_AUDIO_FILE->level());
        $this->assertSame(ErrorLevelEnum::CRITICAL, ErrorTypeEnum::MISSING_WORD_IMAGE_FILE->level());
        $this->assertSame(ErrorLevelEnum::CRITICAL, ErrorTypeEnum::DUPLICATE_WORD->level());
        $this->assertSame(ErrorLevelEnum::CRITICAL, ErrorTypeEnum::PARSE_WORD_INTO_TILES->level());

        $this->assertSame(ErrorLevelEnum::WARNING, ErrorTypeEnum::PARSE_WORD_INTO_KEYS->level());
        $this->assertSame(ErrorLevelEnum::WARNING, ErrorTypeEnum::DUPLICATE_KEY->level());
        $this->assertSame(ErrorLevelEnum::WARNING, ErrorTypeEnum::KEY_NOT_USED_IN_WORDS->level());
        $this->assertSame(ErrorLevelEnum::WARNING, ErrorTypeEnum::MISSING_APP_ID->level());

        $this->assertSame(ErrorLevelEnum::RECOMMENDATION, ErrorTypeEnum::TILE_USAGE->level());
    }

    public function test_only_critical_levels_are_marked_critical(): void
    {
        $this->assertTrue(ErrorTypeEnum::NO_KEYBOARD_KEYS->isCritical());
        $this->assertFalse(ErrorTypeEnum::DUPLICATE_KEY->isCritical());
        $this->assertFalse(ErrorTypeEnum::MISSING_APP_ID->isCritical());
        $this->assertFalse(ErrorTypeEnum::TILE_USAGE->isCritical());
    }
}
