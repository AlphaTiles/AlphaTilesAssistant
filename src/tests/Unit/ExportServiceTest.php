<?php

namespace Tests\Unit;

use ZipArchive;
use App\Models\Key;
use Tests\TestCase;
use App\Models\Tile;
use App\Models\Syllable;
use App\Models\LanguagePack;
use App\Models\LanguageSetting;
use App\Enums\LangInfoEnum;
use App\Services\GenerateZipExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ExportServiceTest extends TestCase
{
    use RefreshDatabase;

    private LanguagePack $languagePack;

    protected function setUp(): void
    {
        parent::setUp();

        $this->languagePack = LanguagePack::factory()->create();

        LanguageSetting::create([
            'languagepackid' => $this->languagePack->id,
            'name' => LangInfoEnum::LANG_NAME_LOCAL->value,
            'value' => 'TestLang'
        ]);
    }

    public function test_space_placeholder_is_converted_to_empty_space_in_tiles_export(): void
    {
        Tile::factory()->create([
            'languagepackid' => $this->languagePack->id,
            'value' => '[space]',
            'upper' => '[space]',
            'or_1' => '[space]',
            'or_2' => 'a',
            'or_3' => '[space]',
        ]);

        $zip = new ZipArchive();
        $exportService = new GenerateZipExportService($this->languagePack);
        $filePath = $exportService->generateTilesFile('aa_gametiles.txt', $zip, 'testApp');

        $content = file_get_contents($filePath);
        $lines = explode("\n", trim($content));
        
        $this->assertGreaterThan(1, count($lines));
        $columns = explode("\t", $lines[1]);

        // Columns: tiles, Or1, Or2, Or3, Type, AudioName, Upper...
        $this->assertEquals(' ', $columns[0]); // Tile value
        $this->assertEquals(' ', $columns[1]); // Or1
        $this->assertEquals('a', $columns[2]); // Or2
        $this->assertEquals(' ', $columns[3]); // Or3
        $this->assertEquals(' ', $columns[6]); // Upper
    }

    public function test_space_placeholder_is_converted_to_empty_space_in_syllables_export(): void
    {
        Syllable::create([
            'languagepackid' => $this->languagePack->id,
            'value' => '[space]',
            'or_1' => '[space]',
            'or_2' => 'ba',
            'or_3' => '[space]',
            'color' => 1
        ]);

        $zip = new ZipArchive();
        $exportService = new GenerateZipExportService($this->languagePack);
        $filePath = $exportService->generateSyllablesFile('aa_syllables.txt', $zip, 'testApp');

        $content = file_get_contents($filePath);
        $lines = explode("\n", trim($content));

        $this->assertGreaterThan(1, count($lines));
        $columns = explode("\t", $lines[1]);

        // Columns: Syllable, Or1, Or2, Or3...
        $this->assertEquals(' ', $columns[0]); // Syllable value
        $this->assertEquals(' ', $columns[1]); // Or1
        $this->assertEquals('ba', $columns[2]); // Or2
        $this->assertEquals(' ', $columns[3]); // Or3
    }

    public function test_uppercase_space_placeholder_is_converted_to_empty_space(): void
    {
        Tile::factory()->create([
            'languagepackid' => $this->languagePack->id,
            'value' => '[SPACE]',
            'upper' => '[SPACE]',
            'or_1' => '[SPACE]',
            'or_2' => 'b',
            'or_3' => '[SPACE]',
        ]);

        $zip = new ZipArchive();
        $exportService = new GenerateZipExportService($this->languagePack);
        $filePath = $exportService->generateTilesFile('aa_gametiles.txt', $zip, 'testApp');

        $content = file_get_contents($filePath);
        $lines = explode("\n", trim($content));

        $this->assertGreaterThan(1, count($lines));
        $columns = explode("\t", $lines[1]);

        $this->assertEquals(' ', $columns[0]); // Tile value
        $this->assertEquals(' ', $columns[1]); // Or1
        $this->assertEquals(' ', $columns[3]); // Or3
        $this->assertEquals(' ', $columns[6]); // Upper
    }
}
