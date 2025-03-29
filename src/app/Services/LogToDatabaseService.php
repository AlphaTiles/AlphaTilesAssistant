<?php

namespace App\Services;

use App\Enums\ExportStatus;
use App\Models\DatabaseLog;
use Illuminate\Support\Facades\Log;

class LogToDatabaseService
{
    public int $languagepackId;
    public string $type;

    public function __construct(int $languagepackId, string $type)
    {
        $this->languagepackId = $languagepackId;
        $this->type = $type;
    }

    public function handle(string $message, ExportStatus $status): void
    {
        $previousLog = null;
        $newMessage = $message;

        Log::error('Status: ' . $status->value);

        if ($status != ExportStatus::STARTED) {
            $previousLog = DatabaseLog::where('languagepackid', $this->languagepackId)
            ->where('type', $this->type)
            ->latest()
            ->first();            
        }

        $statusValue = $status->value;
        if (!empty($previousLog)) {
            $newMessage = $previousLog->message . "\n" . $message;
            if ($previousLog->status === ExportStatus::FAILED) {
                $statusValue = $previousLog->status;
            }
        }

        DatabaseLog::updateOrCreate([
            'languagepackid' => $this->languagepackId,
            'type' => $this->type,
        ],[
            'message' => $newMessage,
            'status' => $statusValue,
        ]);    
    }
}