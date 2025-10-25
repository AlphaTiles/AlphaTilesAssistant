<?php

namespace App\Services;

use App\Enums\ExportStatus;
use Exception;
use Google\Client;
use App\Models\Key;
use App\Models\Tile;
use App\Models\Word;
use App\Models\Syllable;
use Google\Service\Drive;
use Google\Service\Sheets;
use App\Enums\LangInfoEnum;
use App\Enums\FieldTypeEnum;
use App\Models\LanguagePack;
use App\Enums\GameSettingEnum;
use App\Models\LanguageSetting;
use App\Models\Resource;
use Google\Service\Drive\DriveFile;
use Illuminate\Support\Facades\Log;
use Google\Service\Sheets\ValueRange;
use Illuminate\Support\Facades\Storage;
use App\Repositories\LangInfoRepository;
use App\Repositories\GameSettingsRepository;
use Google\Service\Sheets\BatchUpdateSpreadsheetRequest;

class ExportSheetService
{    
    protected LogToDatabaseService $logService;
    protected GoogleService $googleService;
    protected Drive $driveService;
    protected Sheets $googleSheet;
    protected LanguagePack $languagePack;
    protected Client $client;
    protected string $spreadSheetId;
    protected string $exportFolderId;
    protected bool $debug;    

    public function __construct(LanguagePack $languagePack, string $googleToken, string $exportFolderId)
    {
        $this->debug = env('DEBUG', false);
        $this->client = new Client();
        $this->client->setAccessToken($googleToken);
        $this->languagePack = $languagePack;    
        $this->googleService = new GoogleService($languagePack, $googleToken, 'export'); 
        $this->logService = new LogToDatabaseService($languagePack->id, 'export');
        $this->driveService = new Drive($this->client);     
        $this->googleSheet = new Sheets($this->client);    
        $this->exportFolderId = $exportFolderId;
    }

    public function handle(string $folderId)
    {                
        $spreadsheetId = $this->createSpreadsheet($folderId);  
        $this->notesSheet($spreadsheetId);
        $this->langInfoSheet($spreadsheetId);
        $this->tilesSheet($spreadsheetId);
        $this->wordlistSheet($spreadsheetId);        
        $this->keyboardSheet($spreadsheetId);
        $this->syllablesSheet($spreadsheetId);
        $this->resourcesSheet($spreadsheetId);
        $this->settingsSheet($spreadsheetId);
        $this->shareSheet($spreadsheetId);
        $this->namesSheet($spreadsheetId);
        $this->gamesSheet($spreadsheetId);
        $this->colorsSheet($spreadsheetId);
        $this->createFontFolder();
        $this->logService->handle('Export Job completed', ExportStatus::SUCCESS);
    }

    private function notesSheet(string $spreadsheetId): void
    {
        $sheetName = 'notes';
        $this->createSheetTab($spreadsheetId, $sheetName, 0);
        $sheetAndRange = "{$sheetName}!A1:B1"; 
        $values = [
            ["#", "1"],
        ];
        $this->addValuesToSheet($spreadsheetId, $sheetAndRange, $values);
    }

    private function langInfoSheet(string $spreadsheetId): void
    {
        $sheetName = 'langinfo';
        $this->createSheetTab($spreadsheetId, $sheetName, 1);
        $sheetAndRange = "{$sheetName}!A1:B15"; 

        $values = [
            ["Item", "Answer"],
        ];

        $langItems = app(LangInfoRepository::class)->getSettings(false, $this->languagePack);
        $i = 1;
        foreach($langItems as $item) {
            $value = !empty($item['value']) ? $item['value'] : 'none';
            $values[$i] = [$item['export_key'], $value];
            $i++;
        }

        $this->addValuesToSheet($spreadsheetId, $sheetAndRange, $values);
    }

    private function tilesSheet(string $spreadsheetId): void
    {
        $folderName = 'audio_tiles_optional';
        $oldFolderId = $this->googleService->folderExists($folderName, $this->exportFolderId);
        if($oldFolderId) {
            $this->googleService->deleteFolder($oldFolderId);             
        }
        
        $folderId = $this->googleService->createFolder($folderName, $this->exportFolderId);        
        $sheetName = 'gametiles';
        $this->createSheetTab($spreadsheetId, $sheetName, 2);
        $sheetAndRange = "{$sheetName}!A1:Q100"; 

        $values = [[
                'tiles', 'Or1', 'Or2', 'Or3', 'Type', 'AudioName', 'Upper', 'Type2', 'AudioName2',
                'Type3', 'AudioName3', 'Duration1', 'Duration2', 'Duration3', 'FirstAppearsInStage...',
                'FirstAppearsInStage...(Type2)', 'FirstAppearsInStage...(Type3)'
        ]];
        
        $tiles = Tile::where('languagepackid', $this->languagePack->id)
            ->orderBy('value')
            ->get();
        $i = 1;        
        foreach($tiles as $tile) {
            $file1 = $tile->file ? basename($tile->file->name) : 'X';   
            if($tile->file) {
                $this->saveFileToDrive($tile->file, $folderId, 'tile audio', $file1);
            }            
            $file2 = $tile->file2 ? basename($tile->file2->name) : 'X';          
            if($tile->file2) {
                $this->saveFileToDrive($tile->file2, $folderId, 'tile audio 2', $file2);  
            }   
            $file3 = $tile->file3 ? basename($tile->file3->name) : 'X';                                 
            if($tile->file3) {
                $this->saveFileToDrive($tile->file3, $folderId, 'tile audio 3', $file3);  
            }
              
            $fileName1 = str_replace('.mp3', '', $file1);       
            $type1 = !empty($tile->type) ? $tile->type : 'none';            
            $fileName2 = str_replace('.mp3', '', $file2);       
            $type2 = !empty($tile->type2) ? $tile->type2 : 'none';            
            $fileName3 = str_replace('.mp3', '', $file3);       
            $type3 = !empty($tile->type3) ? $tile->type3 : 'none';
            $stage1 = $tile->stage ?? '-';
            $stage2 = $tile->stage2 ?? '-';
            $stage3 = $tile->stage3 ?? '-';

            $values[$i] = [
                $tile->value,
                $tile->or_1,
                $tile->or_2,
                $tile->or_3,
                $type1,
                $fileName1,
                $tile->upper,
                $type2,
                $fileName2,
                $type3,
                $fileName3,
                "0", 
                "0", 
                "0", 
                $stage1,
                $stage2,
                $stage3,         
            ];
            if($this->debug && $i == 2) {
                break;
            }
            $i++;
        }        

        $this->addValuesToSheet($spreadsheetId, $sheetAndRange, $values);
        Log::error('export of tiles completed');
    }

    private function wordlistSheet(string $spreadsheetId): void
    {
        $sheetName = 'wordlist';

        $imageFolderName = 'images_words';        
        $oldFolderId = $this->googleService->folderExists($imageFolderName, $this->exportFolderId);        
        if($oldFolderId) {
            $this->googleService->deleteFolder($oldFolderId);                     
        }        
        $imageFolderId = $this->googleService->createFolder($imageFolderName, $this->exportFolderId);        

        $imageFolderNameLowRes = 'images_words_low_res';
        $oldFolderLowResId = $this->googleService->folderExists($imageFolderNameLowRes, $this->exportFolderId);        
        if($oldFolderLowResId) {
            $this->googleService->deleteFolder($oldFolderLowResId);                     
        }        
        $this->googleService->createFolder($imageFolderNameLowRes, $this->exportFolderId);        


        $wordFolderName = 'audio_words';
        $oldFolderId = $this->googleService->folderExists($wordFolderName, $this->exportFolderId);
        if($oldFolderId) {
            $this->googleService->deleteFolder($oldFolderId);             
        }        
        $audioFolderId = $this->googleService->createFolder($wordFolderName, $this->exportFolderId);        
        Log::error('audio folder: ' . $audioFolderId);        
        $this->createSheetTab($spreadsheetId, $sheetName, 3);
        $sheetAndRange = "{$sheetName}!A1:F2000"; 

        $localLangName = LanguageSetting::where('languagepackid', $this->languagePack->id)
            ->where('name', LangInfoEnum::LANG_NAME_LOCAL->value)->first()->value;
        $values = [[
            'FileName', $localLangName, 'Duration', 'MixedTypes', 'Adjustment',
                        "FirstAppearsInStage(IFOverrulingDefault)..."
        ]];        

        $words = Word::where('languagepackid', $this->languagePack->id)
            ->orderBy('value')
            ->get();
        $i = 1;        
        foreach($words as $word) {
            $fileAudioName = '';
            $fileImageName = '';
            $fileNameSheet = '';
            if(isset($word->audioFile->file_path)) {
                $storagePath = "/storage/languagepacks/{$this->languagePack->id}/res/raw/";
                $fileAudioName = str_replace($storagePath, '', $word->audioFile->file_path);
                $fileNameSheet = str_replace('.mp3', '',$fileAudioName);
            }
            if(isset($word->imageFile->file_path)) {
                $storagePath = "/storage/languagepacks/{$this->languagePack->id}/res/raw/";
                $fileImageName = str_replace($storagePath, '', $word->imageFile->file_path);
                $fileNameSheet = str_replace('.png', '',$fileImageName);
            }
            $this->saveFileToDrive($word->audioFile, $audioFolderId, 'word audio', $fileAudioName);
            $this->saveFileToDrive($word->imageFile, $imageFolderId, 'image', $fileImageName);

            $mixedTypes = !empty($word->mixed_types) ? $word->mixed_types : '-';  
            $stage = $word->stage ?? '-';

            $values[$i] = [
                $fileNameSheet,
                $word->value,
                '0',
                $mixedTypes,
                '0',
                $stage
            ];
            if($this->debug && $i == 2) {
                break;
            }
            $i++;
        }

        $this->addValuesToSheet($spreadsheetId, $sheetAndRange, $values);
        Log::info('export of words completed');
    }

    private function syllablesSheet(string $spreadsheetId): void
    {
        $sheetName = 'syllables';
        $this->createSheetTab($spreadsheetId, $sheetName, 5);
        $sheetAndRange = "{$sheetName}!A1:G100"; 

        $values = [
            ["Syllable", "Or1", "Or2", "Or3", "SyllableAudioName", "Duration", "Color"],
        ];

        $folderName = 'audio_syllables_optional';
        $oldFolderId = $this->googleService->folderExists($folderName, $this->exportFolderId);
        if($oldFolderId) {
            $this->googleService->deleteFolder($oldFolderId);             
        }
        
        $folderId = $this->googleService->createFolder($folderName, $this->exportFolderId);        
        $sheetName = 'syllables';
        $this->createSheetTab($spreadsheetId, $sheetName, 2);
        
        $items = Syllable::where('languagepackid', $this->languagePack->id)
            ->orderBy('value')
            ->get();
        $i = 1;        
        foreach($items as $item) {
            $file1 = $item->file ? basename($item->file->name) : 'X';   
            if($item->file) {
                $this->saveFileToDrive($item->file, $folderId, 'syllable audio', $file1);
            }            
              
            $fileName1 = str_replace('.mp3', '', $file1);       

            $values[$i] = [
                $item->value,
                $item->or_1,
                $item->or_2,
                $item->or_3,
                $fileName1,
                "0", 
                $item->color,
            ];
            $i++;
        }        

        $this->addValuesToSheet($spreadsheetId, $sheetAndRange, $values);
        Log::error('export of syllables completed');        
    }    

    private function keyboardSheet(string $spreadsheetId): void
    {
        $sheetName = 'keyboard';
        $this->createSheetTab($spreadsheetId, $sheetName, 4);
        $sheetAndRange = "{$sheetName}!A1:B100"; 

        $values = [
            ['keys', 'theme_color'],
        ];

        $keys = Key::where('languagepackid', $this->languagePack->id)
            ->orderBy('id')
            ->get();

        $i = 1;
        foreach ($keys as $keyItem) {            
            $values[$i] = [
                $keyItem->value,
                $keyItem->color
            ];
            $i++;
        }

        $this->addValuesToSheet($spreadsheetId, $sheetAndRange, $values);
        Log::info('export of keyboard completed');
    }      

    private function resourcesSheet(string $spreadsheetId): void
    {        
        $sheetName = 'resources';        
        $this->createSheetTab($spreadsheetId, $sheetName, 6);
        $sheetAndRange = "{$sheetName}!A1:C50"; 

        $folderName = 'images_resources_optional';
        $oldFolderId = $this->googleService->folderExists($folderName, $this->exportFolderId);
        if($oldFolderId) {
            $this->googleService->deleteFolder($oldFolderId);             
        }        
        $imageFolderId = $this->googleService->createFolder($folderName, $this->exportFolderId);        

        $values = [
            ['Name', 'Link', 'Image'],
        ];

        $items = Resource::where('languagepackid', $this->languagePack->id)
            ->orderBy('name')
            ->get();
        $i = 1;        
        $storagePath = "/storage/languagepacks/{$this->languagePack->id}/res/raw/";

        foreach($items as $item) {
            $fileImageName = str_replace($storagePath, '', $item->file->file_path);
            $fileNameSheet = str_replace('.png', '',$fileImageName);
            $this->saveFileToDrive($item->file, $imageFolderId, 'image', $fileImageName);

            $values[$i] = [
                $item->name,
                $item->link,
                $fileNameSheet
            ];
            $i++;
        }        


        $this->addValuesToSheet($spreadsheetId, $sheetAndRange, $values);
        Log::info('export of resources completed');
    }       

    private function settingsSheet(string $spreadsheetId): void
    {
        $sheetName = 'settings';
        $this->createSheetTab($spreadsheetId, $sheetName, 7);
        $sheetAndRange = "{$sheetName}!A1:B20"; 

        $values = [
            ["Setting", "Value"],
        ];

        $items = app(GameSettingsRepository::class)->getSettings(false, $this->languagePack);
        $i = 1;
        foreach($items as $item) {
            if($item['name'] === GameSettingEnum::SHARE_LINK->value) {
                continue;
            }

            $value = $item['value'];
            if ($item['type'] === FieldTypeEnum::CHECKBOX) {
                $value = $item['value'] ? 'TRUE' : 'FALSE';
            } 
            $values[$i] = [$item['export_key'], $value];
            $i++;
        }

        $this->addValuesToSheet($spreadsheetId, $sheetAndRange, $values);
        Log::info('export of settings completed');
    }      

    private function shareSheet(string $spreadsheetId): void
    {
        $sheetName = 'share';
        $this->createSheetTab($spreadsheetId, $sheetName, 8);
        $sheetAndRange = "{$sheetName}!A1:A2"; 

        $items = app(GameSettingsRepository::class)->getSettings(false, $this->languagePack);
        $values = [
            ['Link'], 
        ];
        foreach($items as $item) {
            if($item['name'] === GameSettingEnum::SHARE_LINK->value) {
                $values[] = [$item['value']];
            }
        }

        $this->addValuesToSheet($spreadsheetId, $sheetAndRange, $values);
        Log::info('export of share link completed');
    }      

    private function namesSheet(string $spreadsheetId): void
    {
        $sheetName = 'names';
        $this->createSheetTab($spreadsheetId, $sheetName, 9);
        $sheetAndRange = "{$sheetName}!A1:B1"; 

        $values = [
            ['Entry', 'Name'],
        ];

        $this->addValuesToSheet($spreadsheetId, $sheetAndRange, $values);
        Log::info('export of names completed');
    }  

    private function gamesSheet(string $spreadsheetId): void
    {
        $sheetName = 'games';
        $folderName = 'audio_instructions_optional';
        $oldFolderId = $this->googleService->folderExists($folderName, $this->exportFolderId);
        if($oldFolderId) {
            $this->googleService->deleteFolder($oldFolderId);             
        }        
        $this->googleService->createFolder($folderName, $this->exportFolderId);        

        $this->createSheetTab($spreadsheetId, $sheetName, 10);
        $sheetAndRange = "{$sheetName}!A1:H200"; 

        $filePath = resource_path('settings/aa_games.txt');
        $fileContents = file_get_contents($filePath);
        $lines = explode(PHP_EOL, $fileContents);

        $values = [];
        $i=0;
        foreach ($lines as $line) {
            $parts = explode("\t", $line);
            $values[$i] = $parts;
            $i++;
        }

        $this->addValuesToSheet($spreadsheetId, $sheetAndRange, $values);
        Log::info('export of games completed');
    }   

    private function colorsSheet(string $spreadsheetId): void
    {
        $sheetName = 'colors';
        $this->createSheetTab($spreadsheetId, $sheetName, 11);
        $sheetAndRange = "{$sheetName}!A1:C20"; 

        $filePath = resource_path('settings/aa_colors.txt');
        $fileContents = file_get_contents($filePath);
        $lines = explode(PHP_EOL, $fileContents);

        $values = [];
        $i=0;
        foreach ($lines as $line) {
            $parts = explode("\t", $line);
            $values[$i] = $parts;
            $i++;
        }

        $this->addValuesToSheet($spreadsheetId, $sheetAndRange, $values);
        Log::info('export of colors completed');
    }      
    
    private function createFontFolder()
    {
        $folderName = 'font';
        $oldFolderId = $this->googleService->folderExists($folderName, $this->exportFolderId);
        if($oldFolderId) {
            $this->googleService->deleteFolder($oldFolderId);             
        }        
        $fontFolderId = $this->googleService->createFolder($folderName, $this->exportFolderId);        

        $fontPath = resource_path('font');
        $files = scandir($fontPath);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $filePath = $fontPath . DIRECTORY_SEPARATOR . $file;
            if (is_file($filePath)) {
                $fileMetadata = new DriveFile([
                    'name' => $file,
                    'parents' => [$fontFolderId],
                ]);

                $content = file_get_contents($filePath);
                $this->driveService->files->create($fileMetadata, [
                    'data' => $content,
                    'mimeType' => mime_content_type($filePath),
                    'uploadType' => 'multipart',
                    'fields' => 'id',
                ]);
            }
        }
    }

    private function createSheetTab(string $spreadsheetId, string $sheetName, int $index): void
    {
        $this->logService->handle("Creating {$sheetName} sheet", ExportStatus::IN_PROGRESS);

        if($this->sheetExists($spreadsheetId, $sheetName)) {
            return;
        }

        $request = new BatchUpdateSpreadsheetRequest(array(
            'requests' => array(
                array(
                    'addSheet' => array(
                        'properties' => array(
                            'title' => $sheetName,
                            'index' => $index
                        )
                    )
                )
            )
        ));        
        $this->googleSheet->spreadsheets->batchUpdate($spreadsheetId, $request);
    }

    private function addValuesToSheet(string $spreadsheetId, string $sheetAndRange, array $values): void
    {
        // Replace null values with empty strings in $values
        array_walk_recursive($values, function (&$value) {
            if (is_null($value)) {
            $value = '';
            }
        });

        try{
            $body = new ValueRange([
                'values' => $values
            ]);
            $params = [
                'valueInputOption' => 'RAW'
            ];
            $this->googleSheet->spreadsheets_values->update($spreadsheetId, $sheetAndRange, $body, $params);            
            return;
        }
        catch(Exception $e) {
            Log::error($e->getMessage());
            $this->logService->handle($e->getMessage(), ExportStatus::FAILED);
        }            
    }

    function sheetExists(string $spreadsheetId, string $sheetName) {
        $existingSheets = $this->googleSheet->spreadsheets->get($spreadsheetId)->sheets;
        foreach ($existingSheets as $sheet) {
            if ($sheet->properties->title == $sheetName) {
                return true;
            }
        }
        return false;
    }

    private function createSpreadsheet(string $folderId): string
    {
        $this->logService->handle('Creating spreadsheet', ExportStatus::IN_PROGRESS);

        $fileName = $this->generateFilename();
        $fileId = $this->googleService->fileExists($fileName, $folderId, 'application/vnd.google-apps.spreadsheet');
        if($fileId) {
            return $fileId;
        }

        $driveService = new Drive($this->client);

        $fileMetadata = new DriveFile([
            'name' => $fileName,
            'parents' => [ $folderId ],
            'mimeType' => 'application/vnd.google-apps.spreadsheet'
        ]);
    
        $file = $driveService->files->create($fileMetadata, [
            'fields' => 'id'
        ]);
        
        return $file->id;
    }

    private function generateFilename(): string
    {
        $settings = app(LangInfoRepository::class)->getSettings(false, $this->languagePack);
        $ethnologueCode = current(array_filter($settings, function ($setting) {
            return $setting['name'] === 'ethnologue_code';
        }))['value'];    
        $langName = current(array_filter($settings, function ($setting) {
            return $setting['name'] === 'lang_name_english';
        }))['value'];    
        $langNameFirstPart = explode(' ', $langName)[0];    
        
        return $ethnologueCode . $langNameFirstPart;
    }

    private function saveFileToDrive($file, string $folderId, string $fileType, string $fileName)
    {
        if(!$file) {
            return;
        }

        $this->logService->handle("Exporting {$fileType} file: {$fileName}", ExportStatus::IN_PROGRESS);

        $relativeFilePath = str_replace('/storage/public', '', $file->file_path);
        $relativeFilePath = str_replace('/storage', '', $relativeFilePath);
        $filePath = Storage::disk('public')->path($relativeFilePath);
        $content = Storage::disk('public')->get($relativeFilePath);            
        Log::error("{$fileType} file: " . $fileName);
        $fileMetadata = new DriveFile([
            'name' => $fileName,
            'parents' => [ $folderId ],
        ]);
    
        $this->driveService->files->create($fileMetadata, [
            'data' => $content,
            'mimeType' => mime_content_type($filePath),
            'uploadType' => 'multipart',
            'fields' => 'id'
        ]);    
    }
}
