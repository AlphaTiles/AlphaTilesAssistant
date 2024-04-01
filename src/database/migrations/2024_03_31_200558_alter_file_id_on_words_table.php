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
        Schema::table('words', function (Blueprint $table) {
            $table->dropForeign(['file_id']);
            $table->foreign('audiofile_id')->references('id')->on('files')
                ->onDelete('cascade');

            $table->dropForeign(['imagefile_id']);
            $table->foreign('imagefile_id')->references('id')->on('files')
                ->onDelete('cascade');    
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('words', function (Blueprint $table) {
            $table->dropForeign(['audiofile_id']);
            $table->foreign('audiofile_id')->references('id')->on('files');

            $table->dropForeign(['imagefile_id']);
            $table->foreign('imagefile_id')->references('id')->on('files');
        });        
    }
};
