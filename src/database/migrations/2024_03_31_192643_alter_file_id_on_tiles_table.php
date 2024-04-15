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
        Schema::table('tiles', function (Blueprint $table) {
            $table->dropForeign(['file_id']);
            $table->foreign('file_id')->references('id')->on('files')
                ->onDelete('cascade');
            $table->dropForeign(['file2_id']);
            $table->foreign('file2_id')->references('id')->on('files')
                ->onDelete('cascade');
            $table->dropForeign(['file3_id']);                
            $table->foreign('file3_id')->references('id')->on('files')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tiles', function (Blueprint $table) {
            $table->dropForeign(['file_id']);
            $table->foreign('file_id')->references('id')->on('files');
            $table->dropForeign(['file2_id']);
            $table->foreign('file2_id')->references('id')->on('files');            
            $table->dropForeign(['file3_id']);
            $table->foreign('file3_id')->references('id')->on('files');            
        });
    }
};
