<?php

namespace App\Services;

use Exception;
use Google\Client;
use App\Models\Key;
use App\Models\Tile;
use App\Models\Word;
use Google\Service\Drive;
use Google\Service\Sheets;
use App\Enums\LangInfoEnum;
use App\Models\LanguagePack;
use App\Models\LanguageSetting;
use Google\Service\Drive\DriveFile;
use Illuminate\Support\Facades\Log;
use Google\Service\Sheets\ValueRange;
use Illuminate\Support\Facades\Storage;
use App\Repositories\LangInfoRepository;
use Google\Service\Sheets\BatchUpdateSpreadsheetRequest;

class ExportSheetService
{    
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
        $this->googleService = new GoogleService($googleToken); 
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
        $this->syllablesSheet($spreadsheetId);
        $this->keyboardSheet($spreadsheetId);
        $this->resourcesSheet($spreadsheetId);
        $this->settingsSheet($spreadsheetId);
        $this->namesSheet($spreadsheetId);
        $this->gamesSheet($spreadsheetId);
        $this->colorsSheet($spreadsheetId);
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
            $values[$i] = [$item['export_key'], $item['value']];
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
        $imageFolderName = 'images_words';
        $oldFolderId = $this->googleService->folderExists($imageFolderName, $this->exportFolderId);
        if($oldFolderId) {
            $this->googleService->deleteFolder($oldFolderId);             
        }        
        $imageFolderId = $this->googleService->createFolder($imageFolderName, $this->exportFolderId);        

        $wordFolderName = 'audio_words';
        $oldFolderId = $this->googleService->folderExists($wordFolderName, $this->exportFolderId);
        if($oldFolderId) {
            $this->googleService->deleteFolder($oldFolderId);             
        }        
        $audioFolderId = $this->googleService->createFolder($wordFolderName, $this->exportFolderId);        
        Log::error('audio folder: ' . $audioFolderId);
        $sheetName = 'wordlist';
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
            $fileName = '';
            if(isset($word->audioFile->file_path)) {
                $storagePath = "/storage/languagepacks/{$this->languagePack->id}/res/raw/";
                $fileName = str_replace($storagePath, '', $word->audioFile->file_path);
                $fileName = str_replace('.mp3', '',$fileName);
            }
            $this->saveFileToDrive($word->audioFile, $audioFolderId, 'word audio', $fileName);
            $this->saveFileToDrive($word->imageFile, $imageFolderId, 'image audio', $fileName);

            $mixedTypes = !empty($word->mixed_types) ? $word->mixed_types : '-';  
            $stage = $word->stage ?? '-';

            $values[$i] = [
                $fileName,
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
        $this->createSheetTab($spreadsheetId, $sheetName, 4);
        $sheetAndRange = "{$sheetName}!A1:G1"; 

        $values = [
            ["Syllable", "Or1", "Or2", "Or3", "SyllableAudioName", "Duration", "Color"],
        ];

        $this->addValuesToSheet($spreadsheetId, $sheetAndRange, $values);
        Log::info('export of syllables completed');
    }    

    private function keyboardSheet(string $spreadsheetId): void
    {
        $sheetName = 'keyboard';
        $this->createSheetTab($spreadsheetId, $sheetName, 5);
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
        $sheetAndRange = "{$sheetName}!A1:C1"; 

        $values = [
            ['Name', 'Link', 'Image'],
        ];

        $this->addValuesToSheet($spreadsheetId, $sheetAndRange, $values);
        Log::info('export of resources completed');
    }       

    private function settingsSheet(string $spreadsheetId): void
    {
        $sheetName = 'settings';
        $this->createSheetTab($spreadsheetId, $sheetName, 7);
        $sheetAndRange = "{$sheetName}!A1:B20"; 

        $filePath = resource_path('settings/aa_settings.txt');
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
        Log::info('export of settings completed');
    }      

    private function namesSheet(string $spreadsheetId): void
    {
        $sheetName = 'names';
        $this->createSheetTab($spreadsheetId, $sheetName, 8);
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
        $this->createSheetTab($spreadsheetId, $sheetName, 9);
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
        $this->createSheetTab($spreadsheetId, $sheetName, 10);
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

    private function createSheetTab(string $spreadsheetId, string $sheetName, int $index): void
    {
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
        try{
            $body = new ValueRange([
                'values' => $values
            ]);
            $params = [
                'valueInputOption' => 'RAW'
            ];
            $this->googleSheet->spreadsheets_values->update($spreadsheetId, $sheetAndRange, $body, $params);            
        }
        catch(Exception $e) {
            Log::error($e->getMessage());
        }    
        return;
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
        if($file) {
            return;
        }

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
