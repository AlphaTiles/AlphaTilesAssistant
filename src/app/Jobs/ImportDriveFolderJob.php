<?php

namespace App\Jobs;

use App\Enums\ImportStatus;
use App\Models\LanguagePack;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use App\Services\GoogleService;
use App\Services\ImportSheetService;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class ImportDriveFolderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 3600;

    public string $folderId;
    public string $token;
    public int $userId;
    public GoogleService $googleService;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(int $userId, string $token, string $folderId)
    {
        $this->token = $token;
        $this->folderId = $folderId;
        $this->userId = $userId;           
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {        
        $this->googleService = new GoogleService($this->token);        
        $languagePack = $this->createLanguagePack();
        $files = $this->googleService->listFiles($this->folderId);
        $spreadsheetId = null;
        $sheetType = 'google';
        foreach ($files as $file) {            
            if($file->getMimeType() === 'application/vnd.google-apps.spreadsheet') {
                $spreadsheetId = $file->id;
            }            
        }                

        //if no Google sheet found, try importing xlsx file isntead
        if(empty($spreadsheetId)) {
            foreach ($files as $file) {
                $fileExtension = pathinfo($file->name, PATHINFO_EXTENSION);            
                if($fileExtension === 'xlsx') {
                    $spreadsheetId = $file->id;
                    $sheetType = 'xlsx';
                    $downloadPath = storage_path(config('app.xlsx.path'));
                    $this->googleService->downloadExcelSheet($spreadsheetId, $downloadPath);                    
                }            
            }                       
        }

        $sheetService = new ImportSheetService($languagePack, $this->token, $this->folderId);
        $sheetService->readAndSaveData($spreadsheetId, $sheetType);
    }

    private function createLanguagePack(): LanguagePack
    {
        $folder = $this->googleService->getFolder($this->folderId);
        $folderName = $folder->getName();
        $existingLanguagePack = LanguagePack::where('userid', $this->userId)
            ->where('name', $folderName)->first();

        if ($existingLanguagePack) {
            // Generate a new name by adding a number
            $folderName = $this->generateUniqueItemName($folderName);
        }

        return LanguagePack::create([
            'userid' => $this->userId,
            'name' => $folderName,
            'import_status' => ImportStatus::IMPORTING
        ]);
    }

    private function generateUniqueItemName($itemName)
    {
        $count = LanguagePack::where('userid', $this->userId)
            ->where('name', 'like', "$itemName%")->count();
        return $itemName . ' ' . ($count + 1);
    }    
}
