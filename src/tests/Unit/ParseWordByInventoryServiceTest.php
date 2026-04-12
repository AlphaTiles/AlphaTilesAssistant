<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\ParseWordByInventoryService;

class ParseWordByInventoryServiceTest extends TestCase
{
    private ParseWordByInventoryService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ParseWordByInventoryService();
    }

    public function test_parses_word_with_simple_inventory()
    {
        $inventory = [
            'a' => 'a',
            'b' => 'b',
            'c' => 'c',
        ];

        $result = $this->service->handle('abc', $inventory, 3);

        $this->assertTrue($result['fully_parsed']);
        $this->assertEquals(['a', 'b', 'c'], $result['items']);
    }

    public function test_parses_word_with_multi_character_inventory()
    {
        $inventory = [
            'ch' => 'ch',
            'a' => 'a',
            't' => 't',
            'z' => 'z',
            'e' => 'e',
        ];

        $result = $this->service->handle('chatze', $inventory, 3);

        $this->assertTrue($result['fully_parsed']);
        $this->assertEquals(['ch', 'a', 't', 'z', 'e'], $result['items']);
    }

    public function test_fails_parsing_with_missing_character()
    {
        $inventory = [
            'a' => 'a',
            'b' => 'b',
        ];

        $result = $this->service->handle('abc', $inventory, 3);

        $this->assertFalse($result['fully_parsed']);
        $this->assertEquals(['a', 'b'], $result['items']);
    }

    public function test_greedy_matching_longest_first()
    {
        $inventory = [
            'sch' => 'sch',
            'ch' => 'ch',
            'c' => 'c',
            'a' => 'a',
        ];

        $result = $this->service->handle('scha', $inventory, 3);

        $this->assertTrue($result['fully_parsed']);
        $this->assertEquals(['sch', 'a'], $result['items']);
    }

    public function test_handles_placeholder_character()
    {
        $inventory = [
            'Xa' => 'á',
            't' => 't',
        ];

        // Normalize input like the services do
        $word = mb_strtolower('tat');
        if (class_exists('Normalizer')) {
            $word = \Normalizer::normalize($word, \Normalizer::FORM_C);
        }

        $result = $this->service->handle($word, $inventory, 3, 'X');

        $this->assertTrue($result['fully_parsed']);
        $this->assertEquals(['t', 'á', 't'], $result['items']);
    }

    public function test_unicode_multibyte_handling()
    {
        $inventory = [
            'ü' => 'ü',
            't' => 't',
            'r' => 'r',
        ];

        $result = $this->service->handle('tür', $inventory, 3);

        $this->assertTrue($result['fully_parsed']);
        $this->assertEquals(['t', 'ü', 'r'], $result['items']);
    }

    public function test_partial_match_with_missing_unicode()
    {
        $inventory = [
            't' => 't',
            'r' => 'r',
        ];

        $result = $this->service->handle('tür', $inventory, 3);

        $this->assertFalse($result['fully_parsed']);
        $this->assertEquals(['t', 'r'], $result['items']);
    }
}
