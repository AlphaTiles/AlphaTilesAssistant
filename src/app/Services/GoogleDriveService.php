<?php

namespace App\Services;

use Exception;
use Google\Client;
use Google\Service\Drive;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Laravel\Socialite\Facades\Socialite;

class GoogleDriveService
{
    protected $client;

    public function __construct()
    {
        $this->client = new Client();
        $this->client->addScope(Drive::DRIVE);
        $this->client->addScope(Drive::DRIVE_FILE);

        $token = Session::get('socialite_token');
        $this->client->setApplicationName("My Application");
        $this->client = new Client();
        $this->client->setAccessToken($token);
    }

    public function listFiles()
    {
        $driveService = new Drive($this->client);

        $query = "mimeType='application/vnd.google-apps.files' and 'root' in parents and trashed=false";
 
        $optParams = [
            'fields' => 'files(id, name)',
            //'q' => $query
        ];
 
        $results = $driveService->files->listFiles($optParams);    

        return $results->getFiles();
    }
}
