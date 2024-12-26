<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('syllables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('languagepackid')->constrained('language_packs')
                ->onDelete('cascade');
            $table->char('value');
            $table->char('or_1')->nullable();
            $table->char('or_2')->nullable();
            $table->char('or_3')->nullable();
            $table->unsignedBigInteger('file_id')->nullable();
            $table->foreign('file_id')->references('id')->on('files')
                ->onDelete('cascade');
            $table->integer('color')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('syllables');
    }
};
