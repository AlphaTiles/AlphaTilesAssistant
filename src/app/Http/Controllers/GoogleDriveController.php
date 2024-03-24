<?php

namespace App\Http\Controllers;

use Google\Service\Drive;
use Illuminate\Http\Request;
use App\Jobs\ImportDriveFolderJob;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Laravel\Socialite\Facades\Socialite;

class GoogleDriveController extends Controller
{        
    public function import()
    {        
        $this->middleware('auth');

        if(!Session::get('has_drive_permissions')) {
            app('redirect')->setIntendedUrl('/drive/import');

            return Socialite::driver('google')
                ->with(['access_type' => 'offline', 'prompt' => 'consent select_account'])
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
