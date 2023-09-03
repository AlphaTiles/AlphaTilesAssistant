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
            $table->char('type2')->after('file_id')->nullable();
            $table->unsignedBigInteger('file2_id')->after('type')->nullable();
            $table->foreign('file2_id')->after('type2')->references('id')->on('files');            
            $table->char('type3')->after('file2_id')->nullable();
            $table->unsignedBigInteger('file3_id')->after('type3')->nullable();
            $table->foreign('file3_id')->after('type2')->references('id')->on('files');            
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
            $table->dropColumn('type2');
            $table->dropColumn('file2_id');
            $table->dropColumn('type3');
            $table->dropColumn('file3_id');
        });
    }
};
