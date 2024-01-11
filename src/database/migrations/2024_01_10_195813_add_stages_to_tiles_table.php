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
        Schema::table('tiles', function (Blueprint $table) {
            $table->unsignedInteger('stage')->after('type')->nullable();
            $table->unsignedInteger('stage2')->after('type2')->nullable();
            $table->unsignedInteger('stage3')->after('type3')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tiles', function (Blueprint $table) {
            $table->dropColumn('stage');
            $table->dropColumn('stage2');
            $table->dropColumn('stage3');
        });
    }
};
