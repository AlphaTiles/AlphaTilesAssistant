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
        Schema::create('languagepack_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('languagepackid')->constrained('language_packs')
                ->onDelete('cascade');            
            $table->string('name');
            $table->string('value');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('languagepack_configs');
    }
};
