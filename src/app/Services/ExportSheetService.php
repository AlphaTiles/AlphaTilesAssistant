<?php

namespace App\Services;

use Exception;
use Google\Client;
use Google\Service\Drive;
use Google\Service\Sheets;
use Illuminate\Support\Arr;
use App\Models\LanguagePack;
use Google\Service\Sheets\Request;
use Google\Service\Drive\DriveFile;
use Illuminate\Support\Facades\Log;
use Google\Service\Sheets\ValueRange;
use App\Repositories\LangInfoRepository;
use Google\Service\Sheets\AddSheetRequest;
use Google\Service\Sheets\SheetProperties;
use Google\Service\Sheets\BatchUpdateSpreadsheetRequest;
use Google\Service\Sheets\Spreadsheet as SheetsSpreadsheet;

class ExportSheetService
{    
    protected GoogleService $googleService;
    protected Sheets $googleSheet;
    protected LanguagePack $languagePack;
    protected Client $client;
    protected string $spreadSheetId;
    protected string $sheetType;
    protected $spreadsheet;

    public function __construct(LanguagePack $languagePack, string $googleToken)
    {
        $this->client = new Client();
        $this->client->setAccessToken($googleToken);
        $this->languagePack = $languagePack;    
        $this->googleService = new GoogleService($googleToken);      
        $this->googleSheet = new Sheets($this->client);    
    }

    public function handle(string $folderId)
    {                
        $spreadsheetId = $this->createSpreadsheet($folderId);  
        $this->notesSheet($spreadsheetId);
        $this->langInfoSheet($spreadsheetId);
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
        $sheetAndRange = "{$sheetName}!A1:B1"; 

        $values = [
            ["Item", "Answer"],
        ];
        $this->addValuesToSheet($spreadsheetId, $sheetAndRange, $values);
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
}
