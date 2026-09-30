<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('drive_exports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('languagepackid')->constrained('language_packs')->onDelete('cascade');
            $table->string('folder_id');
            $table->string('folder_name');
            $table->text('drive_url');
            $table->boolean('is_shared')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drive_exports');
    }
};
