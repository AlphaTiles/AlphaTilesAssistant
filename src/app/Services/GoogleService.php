<?php

namespace App\Services;

use Exception;
use Google\Client;
use Google\Service\Drive;
use App\Enums\ExportStatus;
use App\Models\LanguagePack;
use Google\Service\Drive\DriveFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GoogleService
{
    protected LogToDatabaseService $logService;
    protected Client $client;
    protected DriveFile $driveFile;
    protected Drive $driveService;
    protected string $token;
    protected ?string $refreshToken = null;

    public function __construct(?LanguagePack $languagePack, string $token, $logType = 'unknown', ?string $refreshToken = null)
    {
        $this->client = new Client();
        $this->token = $token;
        $this->refreshToken = $refreshToken;
        
        if($refreshToken) {
            $this->client->setClientId(env('GOOGLE_CLIENT_ID'));
            $this->client->setClientSecret(env('GOOGLE_CLIENT_SECRET'));
        }
        
        $this->client->setAccessToken($token);
        if($refreshToken) {
            // Refresh token support: stored but not set on client directly
            // Token refresh is handled via ensureValidToken() using refreshToken()
        }
        
        $this->driveService = new Drive($this->client);
        $this->driveFile = new DriveFile($this->client);
        if(isset($languagePack)) {
            $this->logService = new LogToDatabaseService($languagePack->id, $logType);
        }
    }
    
    private function ensureValidToken(): void
    {
        try {
            if ($this->client->isAccessTokenExpired()) {
                if ($this->refreshToken) {
                    Log::info('Refreshing expired Google access token');
                    $this->client->refreshToken($this->refreshToken);
                    $this->token = $this->client->getAccessToken()['access_token'] ?? $this->token;
                } else {
                    Log::warning('Access token expired but no refresh token available');
                }
            }
        } catch (Exception $e) {
            Log::error('Error checking/refreshing access token: ' . $e->getMessage());
        }
    }

    public function getFolder($folderId)
    {
        $this->ensureValidToken();
        return $this->driveService->files->get($folderId, [
            'fields' => 'name',
            'supportsAllDrives' => true,
        ]);        
    }

    public function downloadExcelSheet(string $spreadsheetId, string $downloadPath)
    {
        $this->ensureValidToken();
        $response = $this->driveService->files->get($spreadsheetId, [
            'alt' => 'media',
            'supportsAllDrives' => true,
        ]);
        file_put_contents($downloadPath, $response->getBody()->getContents());
    }

    public function listFiles($folderId)
    {        
        $this->ensureValidToken();
        $query = "'{$folderId}' in parents and trashed=false";

        $optParams = [
            'fields' => 'files(id, name, mimeType)',
            'q' => $query,
            'includeItemsFromAllDrives' => true,
            'supportsAllDrives' => true,
        ];
 
        $results = $this->driveService->files->listFiles($optParams);    

        return $results->getFiles();
    }

    function getFileIdByFileName(string $fileName, string $folderPath, string $parentFolderId)
    {
        $optParams = [
            'q' => "'$parentFolderId' in parents and name='$folderPath' and mimeType='application/vnd.google-apps.folder'",
            'fields' => 'files(id)',
            'includeItemsFromAllDrives' => true,
            'supportsAllDrives' => true,
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
            'includeItemsFromAllDrives' => true,
            'supportsAllDrives' => true,
        ];
        $results = $this->driveService->files->listFiles($optParams);

        if (count($results->getFiles()) === 0) {
            return null;
        }
    
        return $results->getFiles()[0]->getId();
    }

    public function saveFile(string $path, string $fileId, string $newFileName): void
    {
        $file = $this->driveService->files->get($fileId, [
            'supportsAllDrives' => true,
        ]);

        // Download file content
        $content = $this->driveService->files->get($fileId, [
            'alt' => 'media',
            'supportsAllDrives' => true,
        ]);

        try {
            // Save file to Laravel storage
            Storage::put($path.$newFileName, $content->getBody()->getContents());
        } catch(Exception $ex) {
            Log::error('exception thrown');
            Log::error($ex->getMessage());
        }
    }

    function fileExists(string $fileName, string $folderId, string $mimeType) 
    {
        $this->ensureValidToken();
        $query = "name='$fileName' and '$folderId' in parents and mimeType='{$mimeType}' and trashed=false";
        
        $response = $this->driveService->files->listFiles([
            'q' => $query,
            'spaces' => 'drive',
            'fields' => 'files(id, name)',
            'includeItemsFromAllDrives' => true,
            'supportsAllDrives' => true,
        ]);
    
        if (count($response->files) > 0) {
            return $response->files[0]->id;
        } else {
            return false;
        }
    }

    function folderExists($folderName, $parentId = null) {
        $folderName = str_replace("'", "\'", $folderName);
        $query = "mimeType='application/vnd.google-apps.folder' and name='{$folderName}' and trashed=false";
        if ($parentId) {
            $query .= " and '{$parentId}' in parents";
        }
    
        $response = $this->driveService->files->listFiles([
            'q' => $query,
            'spaces' => 'drive',
            'fields' => 'files(id)',
            'includeItemsFromAllDrives' => true,
            'supportsAllDrives' => true,
        ]);
    
        if (count($response->files) > 0) {
            return $response->files[0]->id;
        } else {
            return false;
        }
    }    

    function createFolder(string $folderName, $parentId = null, bool $forceCreate = false): string
    {
        $this->ensureValidToken();
        $this->logService->handle("Creating folder $folderName", ExportStatus::IN_PROGRESS);
        if(!$forceCreate) {
            $folderId = $this->folderExists($folderName, $parentId);
            if($folderId) {
                return $folderId;
            }
        }

        $folderMeta = new DriveFile(array(
            'name' => $folderName,
            'mimeType' => 'application/vnd.google-apps.folder'));

        if ($parentId) {
            $folderMeta->setParents([$parentId]);
        }
    
        $folder = $this->driveService->files->create($folderMeta, array(
            'fields' => 'id',
            'supportsAllDrives' => true,
        ));

        return $folder->id;
    }    

    function deleteFolder(string $folderId): void
    {
        try {
            $this->driveService->files->delete($folderId, [
                'supportsAllDrives' => true,
            ]);
        } catch (Exception $e) {
            Log::warning('Ignoring folder delete error during export restart', [
                'folder_id' => $folderId,
                'error' => $e->getMessage(),
            ]);
        }
    }       

    function handleExport(LanguagePack $languagePack, string $driveRootFolderId): void
    {        
        $this->ensureValidToken();
        $folderId = $this->createFolder($languagePack->name, $driveRootFolderId);
        $exportSheetService = new ExportSheetService($languagePack, $this->token, $folderId, $this->refreshToken);
        $exportSheetService->handle($folderId);
    }

}
