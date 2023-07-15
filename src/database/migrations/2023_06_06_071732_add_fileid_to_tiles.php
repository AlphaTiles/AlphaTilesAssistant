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
            $table->unsignedBigInteger('file_id')->after('type')->nullable();
            $table->foreign('file_id')->after('type')->references('id')->on('files');
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
            $table->dropColumn('file_id');
        });
    }
};
