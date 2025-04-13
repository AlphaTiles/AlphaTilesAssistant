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
        Schema::table('language_packs', function (Blueprint $table) {
            // Rename 'userid' to 'user_id'
            $table->renameColumn('userid', 'user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('language_packs', function (Blueprint $table) {
            // Rename 'user_id' back to 'userid'
            $table->renameColumn('user_id', 'userid');
        });
    }
};
