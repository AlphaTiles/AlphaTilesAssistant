<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('existing_apps', function (Blueprint $table) {
            $table->id();
            $table->string('ethnologue_code');
            $table->string('app_id');
            $table->timestamps();
        });

        $this->seedFromCsv();
    }

    /**
     * Seed the table from the CSV file.
     */
    private function seedFromCsv(): void
    {
        $csvPath = database_path('seeders/existing_apps.csv');

        if (!file_exists($csvPath)) {
            return;
        }

        $handle = fopen($csvPath, 'r');

        if ($handle === false) {
            return;
        }

        // The CSV uses \r\n line endings and has multi-line quoted fields.
        // PHP's fgetcsv handles this correctly.

        // Skip the header row (it spans multiple lines due to quoted newlines)
        fgetcsv($handle);

        $rows = [];

        while (($data = fgetcsv($handle)) !== false) {
            // Column index 1 = Ethn+, Column index 2 = App
            if (!isset($data[1], $data[2])) {
                continue;
            }

            $ethnologueCode = trim($data[1]);
            $appId          = trim($data[2]);

            if ($ethnologueCode === '' || $appId === '') {
                continue;
            }

            $rows[] = [
                'ethnologue_code' => $ethnologueCode,
                'app_id'          => $appId,
                'created_at'      => now(),
                'updated_at'      => now(),
            ];
        }

        fclose($handle);

        // Insert in chunks to avoid hitting query size limits
        foreach (array_chunk($rows, 100) as $chunk) {
            DB::table('existing_apps')->insert($chunk);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('existing_apps');
    }
};
