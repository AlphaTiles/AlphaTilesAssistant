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
use App\Models\DatabaseLog;
use Illuminate\Support\Facades\Session;
use Laravel\Socialite\Facades\Socialite;

class GoogleDriveController extends Controller
{     
      
    public function export(LanguagePack $languagePack)
    { 
        $this->middleware('auth');

        if (
            !Session::get('socialite_token')
            || !Session::get('socialite_refresh_token')
            || !Session::get('drive_permissions_time')
            || Session::get('drive_permissions_time') < Carbon::now()->subHour()
        ) {
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

        $token = Session::get("socialite_token") ?? $request->input('token');
        $refreshToken = Session::get("socialite_refresh_token") ?? $request->input('refreshToken');
        if (!$token || !$refreshToken) {
            return response()->json([
                'success' => false,
                'message' => __('Reconnect Google Drive to continue.'),
            ], 401);
        }

        $logService = new LogToDatabaseService($languagePack->id, 'export');
        $logService->handle('Export Job started', ExportStatus::STARTED);

        ExportDriveFolderJob::dispatch($token, $languagePack, $request->input('folderId'), $refreshToken);

        return response()->json([
            'success' => true,
            'folderId' => $request->input('folderId'),
        ]);
    }

    public function browseExportFolders(Request $request, LanguagePack $languagePack)
    {
        $validated = $request->validate([
            'location' => 'required|in:my-drive,shared-with-me,shared-drive',
            'parent_id' => 'nullable|string|max:255',
            'drive_id' => 'nullable|string|max:255',
        ]);

        $service = $this->exportFolderBrowserService();
        if (!$service) {
            return response()->json(['message' => __('Reconnect Google Drive to continue.')], 401);
        }

        try {
            $driveId = $validated['drive_id'] ?? null;
            if ($validated['location'] === 'shared-drive') {
                $allowedDrive = collect($service->listSharedDrivesForBrowser())
                    ->contains(fn (array $drive) => $drive['id'] === $driveId);
                if (!$allowedDrive) {
                    return response()->json(['message' => __('Shared drive not found.')], 403);
                }
            }

            return response()->json([
                'folders' => $service->listFoldersForBrowser(
                    $validated['location'],
                    $validated['parent_id'] ?? null,
                    $driveId,
                ),
            ]);
        } catch (\Throwable $exception) {
            Log::warning('Could not list Google Drive folders for export', [
                'language_pack_id' => $languagePack->id,
                'error' => $exception->getMessage(),
            ]);

            return response()->json(['message' => __('Could not load Google Drive folders.')], 502);
        }
    }

    public function browseExportSharedDrives(LanguagePack $languagePack)
    {
        $service = $this->exportFolderBrowserService();
        if (!$service) {
            return response()->json(['message' => __('Reconnect Google Drive to continue.')], 401);
        }

        try {
            return response()->json([
                'drives' => $service->listSharedDrivesForBrowser(),
            ]);
        } catch (\Throwable $exception) {
            Log::warning('Could not list Google shared drives for export', [
                'language_pack_id' => $languagePack->id,
                'error' => $exception->getMessage(),
            ]);

            return response()->json(['message' => __('Could not load Google Drive folders.')], 502);
        }
    }

    public function createExportFolder(Request $request, LanguagePack $languagePack)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|string|max:255',
        ]);

        $service = $this->exportFolderBrowserService();
        if (!$service) {
            return response()->json(['message' => __('Reconnect Google Drive to continue.')], 401);
        }

        try {
            return response()->json([
                'folder' => $service->createFolderForBrowser(
                    trim($validated['name']),
                    $validated['parent_id'] ?? null,
                ),
            ], 201);
        } catch (\Throwable $exception) {
            Log::warning('Could not create Google Drive folder for export', [
                'language_pack_id' => $languagePack->id,
                'error' => $exception->getMessage(),
            ]);

            return response()->json(['message' => __('Could not create folder in Google Drive.')], 502);
        }
    }

    private function exportFolderBrowserService(): ?GoogleService
    {
        $token = Session::get('socialite_token');
        if (!$token) {
            return null;
        }

        return new GoogleService(
            null,
            $token,
            'export',
            Session::get('socialite_refresh_token'),
        );
    }

    public function cancelExport(Request $request, LanguagePack $languagePack)
    {
        $log = DatabaseLog::where('languagepackid', $languagePack->id)
            ->where('type', 'export')
            ->firstOrFail();

        if (!in_array($log->status, [ExportStatus::STARTED->value, ExportStatus::IN_PROGRESS->value], true)) {
            return response()->json(['success' => false, 'status' => $log->status], 409);
        }

        $log->update([
            'message' => $log->message . "\nExport cancellation requested.",
            'status' => ExportStatus::CANCELLED->value,
        ]);

        return response()->json(['success' => true, 'status' => ExportStatus::CANCELLED->value]);
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
