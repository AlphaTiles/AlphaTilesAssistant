<?php

namespace Tests\Unit;

use App\Models\Key;
use App\Models\Syllable;
use App\Models\LanguagePack;
use App\Services\ImportSheetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

class ImportSheetServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_save_keyboard_handles_rows_without_color_column(): void
    {
        $languagePack = LanguagePack::factory()->create();
        $service = new ImportSheetService($languagePack, 'token', 'folder');

        $sheet = new class([['header'], ['a']]) {
            public function __construct(private array $rows)
            {
            }

            public function toArray(): array
            {
                return $this->rows;
            }
        };

        $spreadsheet = new class($sheet) {
            public function __construct(private $sheet)
            {
            }

            public function getSheetByName(string $sheetName)
            {
                return $this->sheet;
            }
        };

        $this->setProtectedProperty($service, 'sheetType', 'xlsx');
        $this->setProtectedProperty($service, 'spreadsheet', $spreadsheet);

        $method = new ReflectionMethod(ImportSheetService::class, 'saveKeyboard');
        $method->setAccessible(true);
        $method->invoke($service, 'keyboard');

        $this->assertDatabaseHas('keys', [
            'languagepackid' => $languagePack->id,
            'value' => 'a',
            'color' => null,
        ]);
    }

    public function test_save_syllables_converts_space_to_placeholder(): void
    {
        $languagePack = LanguagePack::factory()->create();
        $service = new ImportSheetService($languagePack, 'token', 'folder');

        $sheet = new class([
            ['Syllable', 'Or1', 'Or2', 'Or3', 'SyllableAudioName', 'Duration', 'Color'],
            [' ', ' ', 'ba', ' ', 'X', '0', '5'],
        ]) {
            public function __construct(private array $rows)
            {
            }

            public function toArray(): array
            {
                return $this->rows;
            }
        };

        $spreadsheet = new class($sheet) {
            public function __construct(private $sheet)
            {
            }

            public function getSheetByName(string $sheetName)
            {
                return $this->sheet;
            }
        };

        $this->setProtectedProperty($service, 'sheetType', 'xlsx');
        $this->setProtectedProperty($service, 'spreadsheet', $spreadsheet);

        $method = new ReflectionMethod(ImportSheetService::class, 'saveSyllables');
        $method->setAccessible(true);
        $method->invoke($service, 'syllables');

        $this->assertDatabaseHas('syllables', [
            'languagepackid' => $languagePack->id,
            'value' => '[space]',
            'or_1' => '[space]',
            'or_2' => 'ba',
            'or_3' => '[space]',
            'color' => 5,
        ]);

        $this->assertInstanceOf(Syllable::class, Syllable::first());
    }

    public function test_save_syllables_converts_embedded_spaces_to_placeholder(): void
    {
        $languagePack = LanguagePack::factory()->create();
        $service = new ImportSheetService($languagePack, 'token', 'folder');

        $sheet = new class([
            ['Syllable', 'Or1', 'Or2', 'Or3', 'SyllableAudioName', 'Duration', 'Color'],
            ['Ku ', ' Ku', 'ba', 'a b', 'X', '0', '3'],
        ]) {
            public function __construct(private array $rows)
            {
            }

            public function toArray(): array
            {
                return $this->rows;
            }
        };

        $spreadsheet = new class($sheet) {
            public function __construct(private $sheet)
            {
            }

            public function getSheetByName(string $sheetName)
            {
                return $this->sheet;
            }
        };

        $this->setProtectedProperty($service, 'sheetType', 'xlsx');
        $this->setProtectedProperty($service, 'spreadsheet', $spreadsheet);

        $method = new ReflectionMethod(ImportSheetService::class, 'saveSyllables');
        $method->setAccessible(true);
        $method->invoke($service, 'syllables');

        $this->assertDatabaseHas('syllables', [
            'languagepackid' => $languagePack->id,
            'value' => 'Ku[space]',
            'or_1' => '[space]Ku',
            'or_2' => 'ba',
            'or_3' => 'a[space]b',
            'color' => 3,
        ]);
    }

    private function setProtectedProperty(object $object, string $property, mixed $value): void
    {
        $reflection = new \ReflectionObject($object);
        $property = $reflection->getProperty($property);
        $property->setAccessible(true);
        $property->setValue($object, $value);
    }
}
