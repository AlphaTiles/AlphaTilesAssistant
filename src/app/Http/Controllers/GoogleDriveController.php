<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Google\Service\Drive;
use App\Enums\ExportStatus;
use App\Models\LanguagePack;
use Illuminate\Http\Request;
use App\Services\GoogleService;
use App\Jobs\ExportDriveFolderJob;
use App\Jobs\ImportDriveFolderJob;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Services\LogToDatabaseService;
use Illuminate\Support\Facades\Session;
use Laravel\Socialite\Facades\Socialite;

class GoogleDriveController extends Controller
{     
      
    public function export(LanguagePack $languagePack)
    { 
        $this->middleware('auth');

        if(Session::get('drive_permissions_time') < Carbon::now()->subHour()) {
            app('redirect')->setIntendedUrl('/languagepack/export/' . $languagePack->id);

            return Socialite::driver('google')
                ->scopes([Drive::DRIVE, Drive::DRIVE_FILE])
                ->with(["access_type" => "offline", "prompt" => "consent select_account"])
                ->redirect();        
        }

        return redirect('/languagepack/export/' . $languagePack->id);
    }

    public function dispatchexport(Request $request)
    {
        $request->validate([
            'languagePackId' => 'required|integer',
            'folderId' => 'required|string',
        ]);

        $languagePack = LanguagePack::findOrFail($request->input('languagePackId'));

        $logService = new LogToDatabaseService($languagePack->id, 'export');
        $logService->handle('Export Job started', ExportStatus::STARTED);

        $token = Session::get("socialite_token") ?? $request->input('token');
        $refreshToken = Session::get("socialite_refresh_token") ?? $request->input('refreshToken');

        ExportDriveFolderJob::dispatch($token, $languagePack, $request->input('folderId'), $refreshToken);

        return response()->json([
            'success' => true,
            'folderId' => $request->input('folderId'),
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
