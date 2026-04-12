<?php

namespace Tests\Unit;

use App\Models\Key;
use Tests\TestCase;
use App\Models\Word;
use App\Models\LanguagePack;
use App\Services\ParseWordsIntoKeysService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ParseWordsIntoKeysServiceTest extends TestCase
{
    use RefreshDatabase;

    private LanguagePack $languagePack;
    private ParseWordsIntoKeysService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->languagePack = LanguagePack::factory()->create();
        $this->service = new ParseWordsIntoKeysService($this->languagePack);
    }

    public function test_returns_empty_when_no_keys_exist()
    {
        Word::factory()->create([
            'value' => 'hello',
            'languagepackid' => $this->languagePack->id,
        ]);

        $result = $this->service->handle();

        $this->assertEmpty($result);
    }

    public function test_returns_empty_when_all_words_parse_successfully()
    {
        // Create keys
        foreach (str_split('helo') as $char) {
            Key::factory()->create([
                'value' => $char,
                'languagepackid' => $this->languagePack->id,
            ]);
        }

        Word::factory()->create([
            'value' => 'hello',
            'languagepackid' => $this->languagePack->id,
        ]);

        $result = $this->service->handle();

        $this->assertEmpty($result);
    }

    public function test_detects_missing_character()
    {
        // Create keys without 'ü'
        foreach (str_split('tur') as $char) {
            Key::factory()->create([
                'value' => $char,
                'languagepackid' => $this->languagePack->id,
            ]);
        }

        Word::factory()->create([
            'value' => 'Tür',
            'languagepackid' => $this->languagePack->id,
        ]);

        $result = $this->service->handle();

        $this->assertNotEmpty($result);
        $this->assertArrayHasKey('Tür', $result);
        $this->assertContains('ü', $result['Tür']['missing_characters']);
    }

    public function test_detects_multiple_missing_characters()
    {
        // Create keys only for 'h'
        Key::factory()->create([
            'value' => 'h',
            'languagepackid' => $this->languagePack->id,
        ]);

        Word::factory()->create([
            'value' => 'hello',
            'languagepackid' => $this->languagePack->id,
        ]);

        $result = $this->service->handle();

        $this->assertNotEmpty($result);
        $this->assertArrayHasKey('hello', $result);
        $missingChars = $result['hello']['missing_characters'];
        $this->assertContains('e', $missingChars);
        $this->assertContains('l', $missingChars);
        $this->assertContains('o', $missingChars);
    }

    public function test_ignores_punctuation_in_word()
    {
        // Create keys for 'abc'
        foreach (str_split('abc') as $char) {
            Key::factory()->create([
                'value' => $char,
                'languagepackid' => $this->languagePack->id,
            ]);
        }

        Word::factory()->create([
            'value' => 'a.b.c',
            'languagepackid' => $this->languagePack->id,
        ]);

        $result = $this->service->handle();

        $this->assertEmpty($result);
    }

    public function test_case_insensitive_matching()
    {
        // Create lowercase keys
        foreach (str_split('abc') as $char) {
            Key::factory()->create([
                'value' => $char,
                'languagepackid' => $this->languagePack->id,
            ]);
        }

        Word::factory()->create([
            'value' => 'ABC',
            'languagepackid' => $this->languagePack->id,
        ]);

        $result = $this->service->handle();

        $this->assertEmpty($result);
    }

    public function test_multi_character_keys()
    {
        Key::factory()->create([
            'value' => 'ch',
            'languagepackid' => $this->languagePack->id,
        ]);
        Key::factory()->create([
            'value' => 'a',
            'languagepackid' => $this->languagePack->id,
        ]);
        Key::factory()->create([
            'value' => 't',
            'languagepackid' => $this->languagePack->id,
        ]);
        Key::factory()->create([
            'value' => 'z',
            'languagepackid' => $this->languagePack->id,
        ]);
        Key::factory()->create([
            'value' => 'e',
            'languagepackid' => $this->languagePack->id,
        ]);

        Word::factory()->create([
            'value' => 'chätze',
            'languagepackid' => $this->languagePack->id,
        ]);

        $result = $this->service->handle();

        // Should fail because 'ä' is not in the keys
        $this->assertNotEmpty($result);
        $this->assertContains('ä', $result['chätze']['missing_characters']);
    }

    public function test_parsed_keys_structure()
    {
        // Create keys
        foreach (str_split('ab') as $char) {
            Key::factory()->create([
                'value' => $char,
                'languagepackid' => $this->languagePack->id,
            ]);
        }

        Word::factory()->create([
            'value' => 'abc',
            'languagepackid' => $this->languagePack->id,
        ]);

        $result = $this->service->handle();

        $this->assertNotEmpty($result);
        $this->assertArrayHasKey('parsed_keys', $result['abc']);
        $this->assertArrayHasKey('missing_characters', $result['abc']);
        $this->assertEquals(['a', 'b'], $result['abc']['parsed_keys']);
        $this->assertEquals(['c'], $result['abc']['missing_characters']);
    }
}
