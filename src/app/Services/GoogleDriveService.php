<?php

namespace App\Services;

use Google\Client;
use Google\Service\Drive;
use Illuminate\Support\Facades\Log;

class GoogleDriveService
{
    protected Client $client;
    protected Drive $driveService;

    public function __construct(string $token)
    {
        $this->client = new Client();
        $this->client->setAccessToken($token);
        $this->driveService = new Drive($this->client);
    }

    public function getFolder($folderId)
    {
        return $this->driveService->files->get($folderId, ['fields' => 'name']);        
    }

    public function listFiles($folderId)
    {        
        $query = "'{$folderId}' in parents and trashed=false";

        $optParams = [
            'fields' => 'files(id, name)',
            'q' => $query
        ];
 
        $results = $this->driveService->files->listFiles($optParams);    

        return $results->getFiles();
    }
}
