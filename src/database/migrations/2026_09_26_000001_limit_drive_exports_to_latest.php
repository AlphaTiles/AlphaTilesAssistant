<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $languagePackIds = DB::table('drive_exports')
            ->select('languagepackid')
            ->groupBy('languagepackid')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('languagepackid');

        foreach ($languagePackIds as $languagePackId) {
            $latestId = DB::table('drive_exports')
                ->where('languagepackid', $languagePackId)
                ->max('id');

            DB::table('drive_exports')
                ->where('languagepackid', $languagePackId)
                ->where('id', '<>', $latestId)
                ->delete();
        }

        Schema::table('drive_exports', function (Blueprint $table) {
            $table->unique('languagepackid');
        });
    }

    public function down(): void
    {
        Schema::table('drive_exports', function (Blueprint $table) {
            $table->dropUnique(['languagepackid']);
        });
    }
};
