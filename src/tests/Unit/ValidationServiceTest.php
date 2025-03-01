<?php
namespace Tests\Unit;

use Tests\TestCase;
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
}