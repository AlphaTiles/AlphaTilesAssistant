<?php

namespace Tests\Unit;

use ZipArchive;
use App\Models\Key;
use App\Models\Game;
use Tests\TestCase;
use App\Models\File;
use App\Models\Tile;
use App\Models\Note;
use App\Models\Syllable;
use Google\Service\Drive;
use App\Models\GameSetting;
use App\Models\LanguagePack;
use App\Models\LanguageSetting;
use App\Enums\ExportStatus;
use App\Enums\LangInfoEnum;
use App\Enums\GameSettingEnum;
use App\Services\LogToDatabaseService;
use App\Services\GenerateZipExportService;
use App\Services\ExportSheetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

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

    public function test_embedded_space_placeholder_is_converted_in_syllables_export(): void
    {
        Syllable::create([
            'languagepackid' => $this->languagePack->id,
            'value' => 'Ku[space]',
            'or_1' => 'Ku[space]',
            'or_2' => 'ba',
            'or_3' => '[space]ka',
            'color' => 1
        ]);

        $zip = new ZipArchive();
        $exportService = new GenerateZipExportService($this->languagePack);
        $filePath = $exportService->generateSyllablesFile('aa_syllables.txt', $zip, 'testApp');

        $content = file_get_contents($filePath);
        $lines = explode("\n", trim($content));

        $this->assertGreaterThan(1, count($lines));
        $columns = explode("\t", $lines[1]);

        $this->assertEquals('Ku ', $columns[0]);
        $this->assertEquals('Ku ', $columns[1]);
        $this->assertEquals('ba', $columns[2]);
        $this->assertEquals(' ka', $columns[3]);
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

    public function test_space_placeholder_is_converted_to_empty_space_in_keyboard_export(): void
    {
        Key::create([
            'languagepackid' => $this->languagePack->id,
            'value' => '[space]',
            'color' => '4',
        ]);

        $exportService = new GenerateZipExportService($this->languagePack);
        $filePath = $exportService->generateKeyboardFile('aa_keyboard.txt');

        $content = file_get_contents($filePath);
        $lines = explode("\n", trim($content));

        $this->assertGreaterThan(1, count($lines));
        $columns = explode("\t", $lines[1]);

        $this->assertEquals(' ', $columns[0]);
        $this->assertEquals('4', $columns[1]);
    }

    public function test_notes_are_written_to_the_zip_export_with_defaults_for_missing_timestamps(): void
    {
        Note::create([
            'languagepackid' => $this->languagePack->id,
            'text' => 'first note',
        ]);
        Note::create([
            'languagepackid' => $this->languagePack->id,
            'text' => 'second note',
        ]);

        $exportService = new GenerateZipExportService($this->languagePack);
        $filePath = $exportService->generateNotesFile('aa_notes.txt');

        $content = file_get_contents($filePath);
        $lines = explode("\n", trim($content));

        $this->assertCount(3, $lines); // header + 2 notes

        $firstColumns = explode("\t", $lines[1]);
        $this->assertEquals('1', $firstColumns[0]);
        $this->assertEquals('first note', $firstColumns[1]);
        $this->assertNotEmpty($firstColumns[2]); // created_at defaulted
        $this->assertNotEmpty($firstColumns[3]); // updated_at defaulted

        $secondColumns = explode("\t", $lines[2]);
        $this->assertEquals('2', $secondColumns[0]);
        $this->assertEquals('second note', $secondColumns[1]);
    }

    public function test_google_services_file_is_uploaded_to_drive_root(): void
    {
        Storage::disk('public')->put("languagepacks/{$this->languagePack->id}/res/raw/google_services.json", '{"project_info":{}}');

        $file = File::create([
            'name' => 'google_services.json',
            'file_path' => "/storage/languagepacks/{$this->languagePack->id}/res/raw/google_services.json",
        ]);

        GameSetting::create([
            'languagepackid' => $this->languagePack->id,
            'name' => GameSettingEnum::GOOGLE_SERVICES_JSON->value,
            'value' => (string) $file->id,
        ]);

        $exportService = new ExportSheetService($this->languagePack, 'token', 'drive-root-folder');

        $fakeFilesApi = new class {
            public array $calls = [];
            public array $deleted = [];

            public function create($metadata, array $options)
            {
                $this->calls[] = [
                    'metadata' => $metadata,
                    'options' => $options,
                ];

                return (object) ['id' => 'uploaded-file-id'];
            }

            public function listFiles(array $options)
            {
                return (object) [
                    'files' => [
                        (object) ['id' => 'old-google-services-id'],
                    ],
                ];
            }

            public function delete(string $id, array $options)
            {
                $this->deleted[] = [
                    'id' => $id,
                    'options' => $options,
                ];
            }
        };

        $fakeDriveService = new class($fakeFilesApi) extends Drive {
            public function __construct(private object $fakeFilesApi)
            {
                $this->files = $this->fakeFilesApi;
            }
        };

        $this->setProtectedProperty($exportService, 'driveService', $fakeDriveService);
        $this->setProtectedProperty($exportService, 'logService', new class extends LogToDatabaseService {
            public array $calls = [];

            public function __construct()
            {
            }

            public function handle(string $message, ExportStatus $status): void
            {
                $this->calls[] = compact('message', 'status');
            }
        });

        $method = new \ReflectionMethod(ExportSheetService::class, 'uploadGoogleServicesFileToDriveRoot');
        $method->setAccessible(true);
        $method->invoke($exportService);

        $calls = $fakeFilesApi->calls;
        $deleted = $fakeFilesApi->deleted;

        $this->assertCount(1, $deleted);
        $this->assertSame('old-google-services-id', $deleted[0]['id']);
        $this->assertCount(1, $calls);
        $this->assertSame('google-services.json', $calls[0]['metadata']->name);
        $this->assertSame(['drive-root-folder'], $calls[0]['metadata']->parents);
        $this->assertSame('application/json', $calls[0]['options']['mimeType']);
        $this->assertSame('{"project_info":{}}', $calls[0]['options']['data']);
    }

    public function test_google_services_file_is_added_to_zip_root(): void
    {
        Storage::disk('public')->put("languagepacks/{$this->languagePack->id}/res/raw/custom_google_services.json", '{"project_info":{"project_id":"demo"}}');

        $file = File::create([
            'name' => 'custom_google_services.json',
            'file_path' => "/storage/languagepacks/{$this->languagePack->id}/res/raw/custom_google_services.json",
        ]);

        GameSetting::create([
            'languagepackid' => $this->languagePack->id,
            'name' => GameSettingEnum::GOOGLE_SERVICES_JSON->value,
            'value' => (string) $file->id,
        ]);

        GameSetting::create([
            'languagepackid' => $this->languagePack->id,
            'name' => GameSettingEnum::APP_ID->value,
            'value' => 'testapp',
        ]);

        $zipPath = (new GenerateZipExportService($this->languagePack))->handle();

        $zip = new ZipArchive();
        $zip->open($zipPath);

        $this->assertNotFalse($zip->locateName('testapp/google-services.json'));
        $this->assertSame('{"project_info":{"project_id":"demo"}}', $zip->getFromName('testapp/google-services.json'));

        $zip->close();
    }

    /** @test */
    public function games_file_includes_look_back_window_req_accuracy_and_min_attempts(): void
    {
        Game::factory()->create([
            'languagepackid' => $this->languagePack->id,
            'order' => 1,
            'include' => true,
            'look_back_window' => 15,
            'req_accuracy' => 0.85,
            'min_attempts' => 25,
        ]);

        $exportService = new GenerateZipExportService($this->languagePack);
        $zip = new ZipArchive();
        $gamesFile = $exportService->generateGamesFile('aa_games.txt', $zip, 'testzip');
        $content = file_get_contents($gamesFile);

        $this->assertStringContainsString("LookBackWindow\tReqAccuracy\tMinAttempts", $content);
        $this->assertStringContainsString("\t15\t0.85\t25\t\n", $content);
    }

    private function setProtectedProperty(object $object, string $property, mixed $value): void
    {
        $reflection = new \ReflectionObject($object);
        $property = $reflection->getProperty($property);
        $property->setAccessible(true);
        $property->setValue($object, $value);
    }
}
