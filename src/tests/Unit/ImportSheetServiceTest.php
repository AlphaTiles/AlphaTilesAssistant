<?php

namespace Tests\Unit;

use App\Models\Game;
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

    public function test_save_notes_uses_provided_timestamps_and_defaults_missing_ones(): void
    {
        $languagePack = LanguagePack::factory()->create();
        $service = new ImportSheetService($languagePack, 'token', 'folder');

        $sheet = new class([
            ['#', 'Note', 'CreatedAt', 'UpdatedAt'],
            ['1', 'first note', '2026-01-01 10:00:00', '2026-01-02 10:00:00'],
            ['2', 'second note', '', ''],
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

        $method = new ReflectionMethod(ImportSheetService::class, 'saveNotes');
        $method->setAccessible(true);
        $method->invoke($service, 'notes');

        $this->assertDatabaseHas('notes', [
            'languagepackid' => $languagePack->id,
            'text' => 'first note',
            'created_at' => '2026-01-01 10:00:00',
            'updated_at' => '2026-01-02 10:00:00',
        ]);

        $secondNote = \App\Models\Note::where('text', 'second note')->first();
        $this->assertNotNull($secondNote);
        $this->assertNotNull($secondNote->created_at);
        $this->assertNotNull($secondNote->updated_at);
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

    public function test_save_games_imports_games_for_my_games_list(): void
    {
        $languagePack = LanguagePack::factory()->create();
        $service = new ImportSheetService($languagePack, 'token', 'folder');

        $sheet = new class([
            ['Door', 'Country', 'ChallengeLevel', 'Color', 'InstructionAudio', 'AudioDuration', 'SyllOrTile', 'StagesIncluded', 'Friendly Name', 'LookBackWindow', 'ReqAccuracy', 'MinAttempts'],
            ['1', 'US', '2', '4', 'X', '15', 'tile', '3', 'First Imported Game', '12', '0.85', '15'],
            ['2', 'CA', '5', '8', 'X', '', 'syllable', '-', 'Second Imported Game'],
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

        $method = new ReflectionMethod(ImportSheetService::class, 'saveGames');
        $method->setAccessible(true);
        $method->invoke($service, 'games');

        $this->assertDatabaseHas('games', [
            'languagepackid' => $languagePack->id,
            'order' => 1,
            'door' => 1,
            'include' => true,
            'country' => 'US',
            'level' => 2,
            'color' => 4,
            'audio_duration' => '15',
            'syll_or_tile' => 'tile',
            'stages_included' => 3,
            'friendly_name' => 'First Imported Game',
            'look_back_window' => 12,
            'req_accuracy' => 0.85,
            'min_attempts' => 15,
        ]);

        $this->assertDatabaseHas('games', [
            'languagepackid' => $languagePack->id,
            'order' => 2,
            'door' => 2,
            'include' => true,
            'country' => 'CA',
            'level' => 5,
            'color' => 8,
            'audio_duration' => null,
            'syll_or_tile' => 'syllable',
            'stages_included' => null,
            'friendly_name' => 'Second Imported Game',
            'look_back_window' => 10,
            'req_accuracy' => 0.9,
            'min_attempts' => 10,
        ]);

        $this->assertSame(2, Game::where('languagepackid', $languagePack->id)->count());
    }

    public function test_save_games_skips_seeded_rows_and_selects_existing_seeded_game_for_my_games_list(): void
    {
        $languagePack = LanguagePack::factory()->create();
        $service = new ImportSheetService($languagePack, 'token', 'folder');

        $existingSeededGame = Game::factory()->create([
            'languagepackid' => $languagePack->id,
            'include' => false,
            'country' => 'Romania',
            'level' => 1,
            'color' => 5,
            'syll_or_tile' => 'T',
            'friendly_name' => 'Learn the Tiles',
            'abs' => false,
            'order' => 1,
            'door' => null,
        ]);

        $sheet = new class([
            ['Door', 'Country', 'ChallengeLevel', 'Color', 'InstructionAudio', 'AudioDuration', 'SyllOrTile', 'StagesIncluded', 'Friendly Name'],
            ['1', 'Romania', '1', '5', 'X', 'naWhileMPOnly', 'T', '-', 'Learn the Tiles'],
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

        $method = new ReflectionMethod(ImportSheetService::class, 'saveGames');
        $method->setAccessible(true);
        $method->invoke($service, 'games');

        $this->assertSame(1, Game::where('languagepackid', $languagePack->id)->count());
        $this->assertDatabaseHas('games', [
            'id' => $existingSeededGame->id,
            'include' => true,
        ]);
    }

    public function test_save_games_resets_existing_included_games_before_import(): void
    {
        $languagePack = LanguagePack::factory()->create();
        $service = new ImportSheetService($languagePack, 'token', 'folder');

        $gameToBeUnselected = Game::factory()->create([
            'languagepackid' => $languagePack->id,
            'include' => true,
            'country' => 'FR',
            'level' => 1,
            'color' => 1,
            'syll_or_tile' => 'T',
            'friendly_name' => 'Old Included Game',
            'abs' => false,
            'order' => 1,
            'door' => 1,
        ]);

        $sheet = new class([
            ['Door', 'Country', 'ChallengeLevel', 'Color', 'InstructionAudio', 'AudioDuration', 'SyllOrTile', 'StagesIncluded', 'Friendly Name'],
            ['1', 'US', '2', '4', 'X', '15', 'tile', '3', 'New Imported Game'],
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

        $method = new ReflectionMethod(ImportSheetService::class, 'saveGames');
        $method->setAccessible(true);
        $method->invoke($service, 'games');

        $this->assertDatabaseHas('games', [
            'id' => $gameToBeUnselected->id,
            'include' => false,
        ]);

        $this->assertDatabaseHas('games', [
            'languagepackid' => $languagePack->id,
            'country' => 'US',
            'friendly_name' => 'New Imported Game',
            'include' => true,
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
