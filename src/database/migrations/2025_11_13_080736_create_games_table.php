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
        Schema::create('games', function (Blueprint $table) {
            $table->id();
            $table->boolean('include')->default(true);
            $table->foreignId('languagepackid')->constrained('language_packs')
                ->onDelete('cascade');      
            $table->integer('door')->nullable();
            $table->integer('order');
            $table->string('country');
            $table->integer('level');      
            $table->integer('color');      
            $table->foreignId('file_id')->nullable()->constrained('files')
                ->onDelete('cascade');
            $table->string('audio_duration')->nullable();
            $table->char('syll_or_tile');
            $table->integer('stages_included')->nullable();
            $table->string('friendly_name')->nullable();    
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('games');
    }
};
