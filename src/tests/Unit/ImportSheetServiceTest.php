<?php

namespace Tests\Unit;

use App\Models\Key;
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

    private function setProtectedProperty(object $object, string $property, mixed $value): void
    {
        $reflection = new \ReflectionObject($object);
        $property = $reflection->getProperty($property);
        $property->setAccessible(true);
        $property->setValue($object, $value);
    }
}
