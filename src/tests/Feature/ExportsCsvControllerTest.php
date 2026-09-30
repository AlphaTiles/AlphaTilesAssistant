<?php

namespace Tests\Feature;

use App\Enums\GameSettingEnum;
use App\Enums\LangInfoEnum;
use App\Models\DriveExport;
use App\Models\GameSetting;
use App\Models\LanguagePack;
use App\Models\LanguageSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExportsCsvControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function exports_csv_contains_shared_projects_and_marks_available_apps_true(): void
    {
        $languagePack = LanguagePack::factory()->create();

        LanguageSetting::create([
            'languagepackid' => $languagePack->id,
            'name' => LangInfoEnum::ETHNOLOGUE_CODE->value,
            'value' => 'atd',
        ]);
        LanguageSetting::create([
            'languagepackid' => $languagePack->id,
            'name' => LangInfoEnum::COUNTRY->value,
            'value' => 'Philippines',
        ]);
        LanguageSetting::create([
            'languagepackid' => $languagePack->id,
            'name' => LangInfoEnum::LANG_NAME_LOCAL->value,
            'value' => 'Ata',
        ]);
        LanguageSetting::create([
            'languagepackid' => $languagePack->id,
            'name' => LangInfoEnum::GAME_NAME->value,
            'value' => 'Ogkokolag Ki',
        ]);
        LanguageSetting::create([
            'languagepackid' => $languagePack->id,
            'name' => LangInfoEnum::LANG_NAME_REGIONAL->value,
            'value' => 'Ata',
        ]);
        GameSetting::create([
            'languagepackid' => $languagePack->id,
            'name' => GameSettingEnum::APP_AVAILABILITY->value,
            'value' => 'App available',
        ]);

        DriveExport::create([
            'languagepackid' => $languagePack->id,
            'folder_id' => 'shared-folder-id',
            'folder_name' => 'atd (Ata)',
            'drive_url' => 'https://drive.google.com/drive/folders/shared-folder-id',
            'is_shared' => true,
        ]);
        $personalLanguagePack = LanguagePack::factory()->create();
        DriveExport::create([
            'languagepackid' => $personalLanguagePack->id,
            'folder_id' => 'personal-folder-id',
            'folder_name' => 'Personal copy',
            'drive_url' => 'https://drive.google.com/drive/folders/personal-folder-id',
            'is_shared' => false,
        ]);

        $response = $this->get('/exports.csv');

        $response->assertOk();
        $response->assertDownload('exports.csv');

        $csvLines = preg_split('/\r\n|\n|\r/', trim($response->streamedContent()));
        $rows = array_map('str_getcsv', $csvLines);

        $this->assertSame([
            'iso',
            'Country',
            'Authorized S1',
            'OffStoreAppStatus S2',
            'AndroidVer S3',
            'Folder Name',
            'Folder URL',
            'Name',
            'GameName',
            'LWCName',
            'App Name',
            'App URL',
            'Play Store Link A2',
        ], $rows[0]);

        $this->assertCount(2, $rows);
        $this->assertSame('atd', $rows[1][0]);
        $this->assertSame('Philippines', $rows[1][1]);
        $this->assertSame('TRUE', $rows[1][3]);
        $this->assertSame('atd (Ata)', $rows[1][5]);
        $this->assertSame('https://drive.google.com/drive/folders/shared-folder-id', $rows[1][6]);
        $this->assertSame('Ata', $rows[1][7]);
        $this->assertSame('Ogkokolag Ki', $rows[1][8]);
        $this->assertSame('Ata', $rows[1][9]);
    }
}
