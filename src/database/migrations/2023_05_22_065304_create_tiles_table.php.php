<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('languagepackid')->constrained('language_packs')
            ->onDelete('cascade');      
            $table->char('value');
            $table->char('upper')->nullable();
            $table->char('or_1')->nullable();            
            $table->char('or_2')->nullable();
            $table->char('or_3')->nullable();
            $table->char('type')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tiles');
    }
};
