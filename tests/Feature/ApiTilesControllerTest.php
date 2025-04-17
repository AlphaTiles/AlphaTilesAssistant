<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Tile;
use App\Models\Word;
use App\Models\LangInfo;
use App\Enums\LangInfoEnum;
use App\Models\LanguagePack;
use App\Models\LanguageSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ApiTilesControllerTest extends TestCase
{
    use RefreshDatabase;

    private LanguagePack $languagePack;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test language pack
        $this->languagePack = LanguagePack::factory()->create();

        // Create script type lang info
        LanguageSetting::factory()->create([
            'languagepackid' => $this->languagePack->id,
            'name' => LangInfoEnum::SCRIPT_TYPE->value,
            'value' => 'Roman'
        ]);
    }

    /** @test */
    public function it_returns_words_containing_tile()
    {
        // Arrange
        $tile = Tile::factory()->create([
            'languagepackid' => $this->languagePack->id,
            'value' => 'a'
        ]);

        Word::factory()->create([
            'languagepackid' => $this->languagePack->id,
            'value' => 'cat'
        ]);

        Word::factory()->create([
            'languagepackid' => $this->languagePack->id,
            'value' => 'dog' // Should not be returned
        ]);

        // Act
        $response = $this->getJson("/api/languagepacks/{$this->languagePack->id}/tiles/{$tile->id}/words");

        // Assert
        $response->assertStatus(200)
                ->assertJson(['cat']);
    }

    /** @test */
    public function it_handles_diacritics_correctly()
    {
        // Arrange
        $tile = Tile::factory()->create([
            'languagepackid' => $this->languagePack->id,
            'value' => 'é'
        ]);

        Word::factory()->create([
            'languagepackid' => $this->languagePack->id,
            'value' => 'café'
        ]);

        // Act
        $response = $this->getJson("/api/languagepacks/{$this->languagePack->id}/tiles/{$tile->id}/words");

        // Assert
        $response->assertStatus(200)
                ->assertJson(['café']);
    }

    /** @test */
    public function it_returns_message_when_no_words_found()
    {
        // Arrange
        $tile = Tile::factory()->create([
            'languagepackid' => $this->languagePack->id,
            'value' => 'x'
        ]);

        // Act
        $response = $this->getJson("/api/languagepacks/{$this->languagePack->id}/tiles/{$tile->id}/words");

        // Assert
        $response->assertStatus(200)
                ->assertJson(['No words found with the specified tile.']);
    }

    /** @test */
    public function it_returns_404_for_invalid_language_pack()
    {
        // Arrange
        $tile = Tile::factory()->create([
            'languagepackid' => $this->languagePack->id
        ]);

        // Act
        $response = $this->getJson("/api/languagepacks/999/tiles/{$tile->id}/words");

        // Assert
        $response->assertStatus(404);
    }

    /** @test */
    public function it_returns_404_for_invalid_tile()
    {
        // Act
        $response = $this->getJson("/api/languagepacks/{$this->languagePack->id}/tiles/999/words");

        // Assert
        $response->assertStatus(404);
    }

    /** @test */
    public function it_handles_compound_characters_correctly()
    {
        // Arrange
        $tileC = Tile::factory()->create([
            'languagepackid' => $this->languagePack->id,
            'value' => 'c'
        ]);

        $tileSch = Tile::factory()->create([
            'languagepackid' => $this->languagePack->id,
            'value' => 'sch'
        ]);

        Word::factory()->create([
            'languagepackid' => $this->languagePack->id,
            'value' => 'Hirsch'
        ]);

        // Act & Assert for 'c' tile
        $responseC = $this->getJson("/api/languagepacks/{$this->languagePack->id}/tiles/{$tileC->id}/words");
        $responseC->assertStatus(200)
                 ->assertJson(['No words found with the specified tile.']);

        // Act & Assert for 'sch' tile
        $responseSch = $this->getJson("/api/languagepacks/{$this->languagePack->id}/tiles/{$tileSch->id}/words");
        $responseSch->assertStatus(200)
                   ->assertJson(['Hirsch']);
    }
}