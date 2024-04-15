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
        Schema::table('keys', function (Blueprint $table) {
            $table->dropUnique('keys_value_unique');
            $table->string('value')->collation('utf8mb4_bin')->change();
            $table->unique(['value', 'languagepackid']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('keys', function (Blueprint $table) {            
            $table->dropUnique(['value', 'languagepackid']);
            $table->string('value')->collation('utf8mb4_unicode_ci')->change();
            $table->unique('value');
        });
    }
};
