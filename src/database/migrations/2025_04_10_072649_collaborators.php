<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('collaborators', function (Blueprint $table) {
            $table->id();
            $table->foreignId('languagepack_id')->references('id')->on('language_packs')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
        Schema::table('collaborators', function (Blueprint $table) {
            $table->unique(['languagepack_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('collaborators', function (Blueprint $table) {
            $table->dropUnique(['languagepack_id', 'user_id']);
        });
        Schema::dropIfExists('collaborators');
    }
};
