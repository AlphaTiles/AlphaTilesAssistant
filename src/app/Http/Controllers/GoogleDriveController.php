<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Google\Service\Drive;
use App\Models\LanguagePack;
use Illuminate\Http\Request;
use App\Services\GoogleService;
use App\Jobs\ExportDriveFolderJob;
use App\Jobs\ImportDriveFolderJob;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Laravel\Socialite\Facades\Socialite;

class GoogleDriveController extends Controller
{     
      
    public function export(LanguagePack $languagePack)
    { 
        $this->middleware('auth');

        if(Session::get('drive_permissions_time') < Carbon::now()->subHour()) {
            app('redirect')->setIntendedUrl('/drive/export/' . $languagePack->id);

            return Socialite::driver('google')
                ->scopes([Drive::DRIVE, Drive::DRIVE_FILE])
                ->with(["access_type" => "offline", "prompt" => "consent select_account"])
                ->redirect();        
        }

        $token = Session::get("socialite_token");
        $googleService = new GoogleService($token);  
        $driveRootFolderId = $googleService->createFolder('alphatilesassistant');
        ExportDriveFolderJob::dispatch($token, $languagePack, $driveRootFolderId);        

        return view('drive-export', [
            'driveRootFolderId' => $driveRootFolderId,
            'languagepack' => $languagePack,
        ]);
    }

    public function import()
    {        
        $this->middleware('auth');

        if(Session::get('drive_permissions_time') < Carbon::now()->subHour()) {
            app('redirect')->setIntendedUrl('/drive/import');

            return Socialite::driver('google')
                ->scopes([Drive::DRIVE, Drive::DRIVE_FILE])
                ->redirect();        
        }

        return view('drive-import', [
            'accessToken' => Session::get("socialite_token"),
            'userId' => Auth::user()->id
        ]);
    }

    public function dispatchimport(Request $request)
    {
        ImportDriveFolderJob::dispatch($request->userId, $request->token, $request->folderId);
    }    
}
