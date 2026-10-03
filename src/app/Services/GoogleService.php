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
            $this->client->setClientId(config('services.google.client_id'));
            $this->client->setClientSecret(config('services.google.client_secret'));
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
            $accessToken = $this->client->getAccessToken();
            $tokenExpiryIsKnown = isset($accessToken['expires_in'], $accessToken['created']);

            // Session tokens are often stored as opaque strings, which the
            // Google client treats as expired because they have no expiry data.
            if (!$this->refreshToken) {
                if ($tokenExpiryIsKnown && $this->client->isAccessTokenExpired()) {
                    Log::warning('Google access token is expired and cannot be refreshed');
                }

                return;
            }

            if ($this->client->isAccessTokenExpired()) {
                Log::info('Refreshing expired Google access token');
                $credentials = $this->client->fetchAccessTokenWithRefreshToken($this->refreshToken);
                if (empty($credentials['access_token'])) {
                    throw new Exception('Google did not return a refreshed access token.');
                }
                $this->token = $credentials['access_token'];
                Log::info('Google access token refreshed successfully');
            }
        } catch (Exception $e) {
            Log::error('Error checking/refreshing access token: ' . $e->getMessage());
            throw $e;
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

    /**
     * List folders for the in-app Drive folder browser.
     *
     * @return array<int, array{id: string, name: string, driveId: ?string}>
     */
    public function listFoldersForBrowser(string $location, ?string $parentId = null, ?string $driveId = null): array
    {
        $this->ensureValidToken();

        $query = "mimeType='application/vnd.google-apps.folder' and trashed=false";
        $options = [
            'fields' => 'nextPageToken,files(id,name,driveId,ownedByMe)',
            'pageSize' => 1000,
            'orderBy' => 'name',
            'spaces' => 'drive',
            'includeItemsFromAllDrives' => true,
            'supportsAllDrives' => true,
        ];
        if ($location === 'shared-with-me') {
            $options['corpora'] = 'user';
        }

        if ($parentId !== null) {
            $escapedParentId = str_replace(["\\", "'"], ["\\\\", "\\'"], $parentId);
            $query .= " and '{$escapedParentId}' in parents";
            if ($location === 'shared-drive' && $driveId) {
                $options['corpora'] = 'drive';
                $options['driveId'] = $driveId;
            }
        } elseif ($location === 'shared-drive' && $driveId) {
            $escapedDriveId = str_replace(["\\", "'"], ["\\\\", "\\'"], $driveId);
            $query .= " and '{$escapedDriveId}' in parents";
            $options['corpora'] = 'drive';
            $options['driveId'] = $driveId;
        } elseif ($location !== 'shared-with-me') {
            $query .= " and 'root' in parents";
        }

        $options['q'] = $query;
        $folders = [];
        do {
            $response = $this->driveService->files->listFiles($options);
            foreach ($response->getFiles() as $file) {
                // The old Picker's Shared Folders view used ownedByMe=false.
                if ($location === 'shared-with-me' && $parentId === null && $file->getOwnedByMe() === true) {
                    continue;
                }

                $folders[] = [
                    'id' => $file->getId(),
                    'name' => $file->getName(),
                    'driveId' => $file->getDriveId(),
                ];
            }
            $pageToken = $response->getNextPageToken();
            if ($pageToken) {
                $options['pageToken'] = $pageToken;
            }
        } while ($pageToken);

        return $folders;
    }

    /** @return array<int, array{id: string, name: string}> */
    public function listSharedDrivesForBrowser(): array
    {
        $this->ensureValidToken();
        $options = [
            'fields' => 'nextPageToken,drives(id,name)',
            'pageSize' => 100,
        ];
        $drives = [];
        do {
            $response = $this->driveService->drives->listDrives($options);
            foreach ($response->getDrives() as $drive) {
                $drives[] = [
                    'id' => $drive->getId(),
                    'name' => $drive->getName(),
                ];
            }
            $pageToken = $response->getNextPageToken();
            if ($pageToken) {
                $options['pageToken'] = $pageToken;
            }
        } while ($pageToken);

        return $drives;
    }

    public function createFolderForBrowser(string $name, ?string $parentId = null): array
    {
        $this->ensureValidToken();
        $metadata = new DriveFile([
            'name' => $name,
            'mimeType' => 'application/vnd.google-apps.folder',
        ]);
        if ($parentId && $parentId !== 'root') {
            $metadata->setParents([$parentId]);
        }

        $folder = $this->driveService->files->create($metadata, [
            'fields' => 'id,name,driveId',
            'supportsAllDrives' => true,
        ]);

        return [
            'id' => $folder->getId(),
            'name' => $folder->getName(),
            'driveId' => $folder->getDriveId(),
        ];
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
        $exportSheetService = new ExportSheetService($languagePack, $this->token, $driveRootFolderId, $this->refreshToken);
        $exportSheetService->handle($driveRootFolderId);
    }

}
