<?php

namespace App\Http\Controllers;

use App\Services\GoogleDriveService;

class GoogleDriveController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {        
        $googleDriveService = new GoogleDriveService();
        $files = $googleDriveService->listFiles();
        foreach ($files as $file) {
            dump($file->name);
        }        

    }
}
