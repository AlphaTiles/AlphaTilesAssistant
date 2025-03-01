<?php
namespace Tests\Unit;

use Tests\TestCase;
use App\Models\File;
use App\Models\Tile;
use App\Models\Word;
use Illuminate\Support\Arr;
use App\Enums\ErrorTypeEnum;
use App\Models\LanguagePack;
use App\Services\ValidationService;


class ValidationServiceTest extends TestCase
{
    private LanguagePack $languagePack;
    private ValidationService $validationService;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create language pack
        $this->languagePack = LanguagePack::factory()->create();
        
        // Create tiles
        $tiles = ['a', 'b', 'c', 'ch', 'h', 'sch'];
        foreach ($tiles as $tile) {
            Tile::factory()->create([
                'value' => $tile,
                'languagepackid' => $this->languagePack->id
            ]);
        }
        
        // Create words
        $words = ['Katze', 'Hund', 'Frosch', 'Kuh', 'Buch', 'Milch'];
        foreach ($words as $word) {
            Word::factory()->create([
                'value' => $word,
                'languagepackid' => $this->languagePack->id,
            ]);
        }

        $this->validationService = new ValidationService($this->languagePack);
    }

    public function test_check_tile_usage()
    {
        $result = $this->validationService->handle();

        $this->assertArrayHasKey(ErrorTypeEnum::TILE_USAGE->value, $result);
        
        $tileErrors = collect($result[ErrorTypeEnum::TILE_USAGE->value]);
        
        // Tiles that should have errors (used less than 5 times)
        $expectedUnderusedTiles = ['a', 'b', 'c', 'ch', 'h', 'sch'];
        
        foreach ($expectedUnderusedTiles as $tile) {
            $this->assertTrue(
                $tileErrors->contains(function ($error) use ($tile) {
                    return str_starts_with($error['value'], $tile);
                }),
                "Should contain error for underused tile '$tile'"
            );
        }

        $values = $tileErrors->pluck('value')->toArray();
        $this->assertEquals([
            "a (1)",
            "b (1)",
            "c (0)",
            "ch (2)",
            "h (2)",
            "sch (1)"
        ], $values);
    }

    public function test_check_duplicates()
    {
        // Create duplicate words
        Word::factory()->create([
            'value' => 'Katze', // Duplicate of existing word
            'languagepackid' => $this->languagePack->id,
        ]);
        
        Word::factory()->create([
            'value' => 'Hund', // Another duplicate
            'languagepackid' => $this->languagePack->id,
        ]);

        $result = $this->validationService->handle();

        $this->assertArrayHasKey(ErrorTypeEnum::DUPLICATE_WORD->value, $result);
        
        $duplicateErrors = collect($result[ErrorTypeEnum::DUPLICATE_WORD->value]);
        
        // Check that we have exactly 2 duplicate errors
        $this->assertCount(2, $duplicateErrors);
        
        // Verify specific duplicate words are detected
        $duplicateWords = $duplicateErrors->pluck('value')->toArray();
        $this->assertContains('Katze', $duplicateWords);
        $this->assertContains('Hund', $duplicateWords);
        
        // Verify error structure
        $firstError = $duplicateErrors->first();
        $this->assertEquals(ErrorTypeEnum::DUPLICATE_WORD, $firstError['type']);
        $this->assertEquals(ErrorTypeEnum::DUPLICATE_WORD->tab()->name(), $firstError['tab']);
    }

    public function test_check_duplicates_with_different_cases()
    {
        // Create word with different case
        Word::factory()->create([
            'value' => 'katze', // lowercase version of existing 'Katze'
            'languagepackid' => $this->languagePack->id,
        ]);

        $result = $this->validationService->handle();

        $duplicateErrors = collect($result[ErrorTypeEnum::DUPLICATE_WORD->value]);
        
        // Verify case-insensitive duplicate detection
        $this->assertCount(1, $duplicateErrors);
        $this->assertEquals('Katze', $duplicateErrors->first()['value']);
    }

    public function test_check_word_files_missing()
    {
        // Create a separate language pack for this test
        $testLanguagePack = LanguagePack::factory()->create();
        $validationService = new ValidationService($testLanguagePack);

        // Create actual files using FileFactory
        $audioFile = File::factory()->audio()->create();
        $imageFile = File::factory()->image()->create();

        // Create a word with no audio or image files
        Word::factory()->create([
            'value' => 'TestWord',
            'languagepackid' => $testLanguagePack->id,
            'audiofile_id' => null,
            'imagefile_id' => null
        ]);

        // Create a word with only missing audio file
        Word::factory()->create([
            'value' => 'AudioMissing',
            'languagepackid' => $testLanguagePack->id,
            'audiofile_id' => null,
            'imagefile_id' => $imageFile->id
        ]);

        // Create a word with only missing image file
        Word::factory()->create([
            'value' => 'ImageMissing',
            'languagepackid' => $testLanguagePack->id,
            'audiofile_id' => $audioFile->id,
            'imagefile_id' => null
        ]);

        $result = $validationService->handle();

        // Check for audio file errors
        $audioErrors = collect($result[ErrorTypeEnum::MISSING_WORD_AUDIO_FILE->value]);
        $this->assertCount(2, $audioErrors);
        $audioErrorWords = $audioErrors->pluck('value')->toArray();
        $this->assertContains('TestWord', $audioErrorWords);
        $this->assertContains('AudioMissing', $audioErrorWords);

        // Check for image file errors
        $imageErrors = collect($result[ErrorTypeEnum::MISSING_WORD_IMAGE_FILE->value]);
        $this->assertCount(2, $imageErrors);
        $imageErrorWords = $imageErrors->pluck('value')->toArray();
        $this->assertContains('TestWord', $imageErrorWords);
        $this->assertContains('ImageMissing', $imageErrorWords);

        // Verify error structure
        $firstAudioError = $audioErrors->first();
        $this->assertEquals(ErrorTypeEnum::MISSING_WORD_AUDIO_FILE, $firstAudioError['type']);
        $this->assertEquals(ErrorTypeEnum::MISSING_WORD_AUDIO_FILE->tab()->name(), $firstAudioError['tab']);

        $firstImageError = $imageErrors->first();
        $this->assertEquals(ErrorTypeEnum::MISSING_WORD_IMAGE_FILE, $firstImageError['type']);
        $this->assertEquals(ErrorTypeEnum::MISSING_WORD_IMAGE_FILE->tab()->name(), $firstImageError['tab']);
    }
}