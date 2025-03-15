<?php
namespace Tests\Unit;

use App\Models\Key;
use Tests\TestCase;
use App\Models\File;
use App\Models\Tile;
use App\Models\Word;
use App\Models\Syllable;
use Illuminate\Support\Arr;
use App\Enums\ErrorTypeEnum;
use App\Models\LanguagePack;
use App\Services\ValidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;


class ValidationServiceTest extends TestCase
{
    private LanguagePack $languagePack;
    private ValidationService $validationService;

    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create language pack
        $this->languagePack = LanguagePack::factory()->create();
        
        // Create keys
        $keys = ['a', 'b', 'c'];
        foreach ($keys as $key) {
            Key::factory()->create([
                'value' => $key,
                'languagepackid' => $this->languagePack->id
            ]);
        }

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

    public function test_check_key_usage()
    {
        $result = $this->validationService->handle();

        $this->assertArrayHasKey(ErrorTypeEnum::KEY_USAGE->value, $result);
        
        $keyErrors = collect($result[ErrorTypeEnum::KEY_USAGE->value]);
        
        // Keys that should have errors (used less than 5 times)
        $expectedUnderusedKeys= ['a', 'b', 'c'];
        
        foreach ($expectedUnderusedKeys as $key) {
            $this->assertTrue(
                $keyErrors->contains(function ($error) use ($key) {
                    return str_starts_with($error['value'], $key);
                }),
                "Should contain error for underused key '$key'"
            );
        }

        $values = $keyErrors->pluck('value')->toArray();
        $this->assertEquals([
            "a (1)",
            "b (1)",
            "c (3)",
        ], $values);
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

    public function test_check_tile_distractors()
    {
        $testLanguagePack = LanguagePack::factory()->create();
        $validationService = new ValidationService($testLanguagePack);

        // Create tile with no distractors
        Tile::factory()->create([
            'value' => 'ch',
            'languagepackid' => $testLanguagePack->id,
            'or_1' => null,
            'or_2' => null,
            'or_3' => null
        ]);

        // Create tile with partial distractors
        Tile::factory()->create([
            'value' => 'sch',
            'languagepackid' => $testLanguagePack->id,
            'or_1' => 'sh',
            'or_2' => null,
            'or_3' => null
        ]);

        // Create tile with all distractors (should not trigger error)
        Tile::factory()->create([
            'value' => 'a',
            'languagepackid' => $testLanguagePack->id,
            'or_1' => 'e',
            'or_2' => 'i',
            'or_3' => 'o'
        ]);

        $result = $validationService->handle();

        $tileErrors = $result[ErrorTypeEnum::EMPTY_DISTRACTOR_TILE->value];

        // Should have 2 tiles with distractor errors
        $this->assertCount(2, $tileErrors);
        
        // Verify the tiles with missing distractors
        $errorTiles = collect($tileErrors)->pluck('value')->toArray();
        $this->assertContains('ch', $errorTiles);
        $this->assertContains('sch', $errorTiles);
        $this->assertNotContains('a', $errorTiles);

        // Verify error structure
        $firstError = $tileErrors[0];
        $this->assertEquals(ErrorTypeEnum::EMPTY_DISTRACTOR_TILE, $firstError['type']);
        $this->assertEquals(ErrorTypeEnum::EMPTY_DISTRACTOR_TILE->tab()->name(), $firstError['tab']);
    }

    public function test_check_syllable_distractors()
    {
        // Create a separate language pack for this test
        $testLanguagePack = LanguagePack::factory()->create();
        $validationService = new ValidationService($testLanguagePack);

        // Create syllable with no distractors
        Syllable::factory()->create([
            'value' => 'ba',
            'languagepackid' => $testLanguagePack->id,
            'or_1' => null,
            'or_2' => null,
            'or_3' => null
        ]);

        // Create syllable with partial distractors
        Syllable::factory()->create([
            'value' => 'be',
            'languagepackid' => $testLanguagePack->id,
            'or_1' => 'bi',
            'or_2' => null,
            'or_3' => null
        ]);

        // Create syllable with all distractors (should not trigger error)
        Syllable::factory()->create([
            'value' => 'bo',
            'languagepackid' => $testLanguagePack->id,
            'or_1' => 'bu',
            'or_2' => 'ba',
            'or_3' => 'bi'
        ]);

        $result = $validationService->handle();

        $syllableErrors = $result[ErrorTypeEnum::EMPTY_DISTRACTOR_SYLLABLE->value];

        // Should have 2 syllables with distractor errors
        $this->assertCount(2, $syllableErrors);
        
        // Verify the syllables with missing distractors
        $errorSyllables = collect($syllableErrors)->pluck('value')->toArray();
        $this->assertContains('ba', $errorSyllables);
        $this->assertContains('be', $errorSyllables);
        $this->assertNotContains('bo', $errorSyllables);

        // Verify error structure
        $firstError = $syllableErrors[0];
        $this->assertEquals(ErrorTypeEnum::EMPTY_DISTRACTOR_SYLLABLE, $firstError['type']);
        $this->assertEquals(ErrorTypeEnum::EMPTY_DISTRACTOR_SYLLABLE->tab()->name(), $firstError['tab']);
    }    
}