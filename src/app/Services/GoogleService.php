<?php

namespace App\Services;

use Google\Client;
use Google\Service\Drive;
use Google\Service\Sheets;
use Google\Service\Drive\DriveFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GoogleService
{
    protected Client $client;
    protected DriveFile $driveFile;
    protected Drive $driveService;
    protected Sheets $sheetService;

    public function __construct(string $token)
    {
        $this->client = new Client();
        $this->client->setAccessToken($token);
        $this->driveService = new Drive($this->client);
        $this->driveFile = new DriveFile($this->client);
        $this->sheetService = new Sheets($this->client);
    }

    public function getFolder($folderId)
    {
        return $this->driveService->files->get($folderId, ['fields' => 'name']);        
    }

    public function downloadGoogleSheet(string $spreadsheetId, string $downloadPath)
    {
        $response = $this->driveService->files->get($spreadsheetId, ['alt' => 'media']);
        file_put_contents($downloadPath, $response->getBody()->getContents());
    }

    public function listFiles($folderId)
    {        
        $query = "'{$folderId}' in parents and trashed=false";

        $optParams = [
            'fields' => 'files(id, name, mimeType)',
            'q' => $query
        ];
 
        $results = $this->driveService->files->listFiles($optParams);    

        return $results->getFiles();
    }

    function getFileIdByFileName(string $fileName, string $parentPath)
    {
        $optParams = [
            'q' => "name='$parentPath' and mimeType='application/vnd.google-apps.folder'",
            'fields' => 'files(id)',
        ];
        $results = $this->driveService->files->listFiles($optParams);
    
        // Check if the parent folder exists
        if (count($results->getFiles()) === 0) {
            return null; // Parent folder not found
        }
    
        $parentId = $results->getFiles()[0]->getId();
    
        // Search for the file inside the parent folder
        $optParams = [
            'q' => "'$parentId' in parents and name='$fileName'",
            'fields' => 'files(id)',
        ];
        $results = $this->driveService->files->listFiles($optParams);

        if (count($results->getFiles()) === 0) {
            return null;
        }
    
        return $results->getFiles()[0]->getId();
    }

    public function saveFile(string $path, string $fileId, string $newFileName): void
    {
        $file = $this->driveService->files->get($fileId);

        // Download file content
        $content = $this->driveService->files->get($fileId, ['alt' => 'media']);

        // Save file to Laravel storage
        Storage::put($path.$newFileName, $content->getBody()->getContents());
    }
}
