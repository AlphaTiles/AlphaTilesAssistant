<?php

namespace App\Http\Controllers;

use App\Enums\GameSettingEnum;
use App\Enums\LangInfoEnum;
use App\Models\DriveExport;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportsCsvController extends Controller
{
    public function download(): StreamedResponse
    {
        $exports = DriveExport::query()
            ->where('is_shared', true)
            ->with(['languagePack.langInfo', 'languagePack.gameSettings'])
            ->orderBy('folder_name')
            ->get();

        $headers = [
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
        ];

        return response()->streamDownload(function () use ($exports, $headers): void {
            $output = fopen('php://output', 'w');
            fputcsv($output, $headers);

            foreach ($exports as $export) {
                $languagePack = $export->languagePack;
                if (!$languagePack) {
                    continue;
                }

                $languageInfo = $languagePack->langInfo->keyBy('name');
                $gameSettings = $languagePack->gameSettings->keyBy('name');
                $languageValue = static fn (LangInfoEnum $key): string =>
                    (string) ($languageInfo->get($key->value)?->value ?? '');
                $availability = $gameSettings->get(GameSettingEnum::APP_AVAILABILITY->value)?->value
                    ?? GameSettingEnum::APP_AVAILABILITY->defaultValue();

                fputcsv($output, [
                    $languageValue(LangInfoEnum::ETHNOLOGUE_CODE),
                    $languageValue(LangInfoEnum::COUNTRY),
                    'pending',
                    $availability === 'App available' ? 'TRUE' : $availability,
                    '',
                    $export->folder_name,
                    $export->drive_url,
                    $languageValue(LangInfoEnum::LANG_NAME_LOCAL),
                    $languageValue(LangInfoEnum::GAME_NAME),
                    $languageValue(LangInfoEnum::LANG_NAME_REGIONAL),
                    '',
                    '',
                    '',
                ]);
            }

            fclose($output);
        }, 'exports.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
