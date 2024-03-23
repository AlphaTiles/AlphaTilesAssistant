<?php

namespace App\Jobs;

use App\Enums\ImportStatus;
use App\Models\LanguagePack;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use App\Services\GoogleDriveService;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class ImportDriveFolderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $folderId;
    public string $token;
    public int $userId;
    public GoogleDriveService $googleDriveService;

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
        $this->googleDriveService = new GoogleDriveService($this->token);        
        $this->createLanguagePack();
        $files = $this->googleDriveService->listFiles($this->folderId);
        foreach ($files as $file) {
            Log::error($file->name);
        }                
    }

    private function createLanguagePack(): void
    {
        $folder = $this->googleDriveService->getFolder($this->folderId);
        $folderName = $folder->getName();
        $existingLanguagePack = LanguagePack::where('userid', $this->userId)
            ->where('name', $folderName)->first();

        if ($existingLanguagePack) {
            // Generate a new name by adding a number
            $folderName = $this->generateUniqueItemName($folderName);
        }

        LanguagePack::create([
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
