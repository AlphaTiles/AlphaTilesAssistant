<?php

namespace App\Services;

use Google\Client;
use Google\Service\Drive;
use Illuminate\Support\Facades\Session;

class GoogleDriveService
{
    protected $client;

    public function __construct()
    {
        $this->client = new Client();
        $token = Session::get('socialite_token');
        $this->client->setAccessToken($token);
    }

    public function listFiles()
    {
        $driveService = new Drive($this->client);

        $query = "mimeType='application/vnd.google-apps.folder' and 'root' in parents and trashed=false";
 
        $optParams = [
            'fields' => 'files(id, name)',
            'q' => $query
        ];
 
        $results = $driveService->files->listFiles($optParams);    

        return $results->getFiles();
    }
}
