<?php

namespace App\Jobs;

use App\Enums\ExportStatus;
use App\Exceptions\ExportCancelledException;
use App\Enums\ImportStatus;
use App\Models\DatabaseLog;
use App\Models\LanguagePack;
use Illuminate\Bus\Queueable;
use App\Services\GoogleService;
use Illuminate\Support\Facades\Log;
use App\Services\ImportSheetService;
use App\Services\LogToDatabaseService;
use Google\Service\Vault\ExportStats;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class ExportDriveFolderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 3600;

    public string $token;
    public LanguagePack $languagePack;
    public GoogleService $googleService;
    public string $driveRootFolderId;
    public ?string $refreshToken = null;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(string $token, $languagePack, string $driveRootFolderId, ?string $refreshToken = null)
    {
        $this->token = $token;
        $this->languagePack = $languagePack;
        $this->driveRootFolderId = $driveRootFolderId;
        $this->refreshToken = $refreshToken;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {        
        try {
            $this->googleService = new GoogleService($this->languagePack, $this->token, 'export', $this->refreshToken);
            $this->googleService->handleExport($this->languagePack, $this->driveRootFolderId);
        } catch (ExportCancelledException $e) {
            // Cancellation is a normal user action, not a failed queue job.
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Google Drive export job failed', [
            'language_pack_id' => $this->languagePack->id,
            'error' => $exception->getMessage(),
        ]);

        $log = DatabaseLog::where('languagepackid', $this->languagePack->id)
            ->where('type', 'export')
            ->first();
        if ($log?->status === ExportStatus::CANCELLED->value) {
            return;
        }

        (new LogToDatabaseService($this->languagePack->id, 'export'))
            ->handle('Export failed. Check the application log for details.', ExportStatus::FAILED);
    }
}
