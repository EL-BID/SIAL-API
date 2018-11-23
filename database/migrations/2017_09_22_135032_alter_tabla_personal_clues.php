<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTablaPersonalClues extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('personal_clues', function (Blueprint $table) {
            $table->integer('tipo_personal_id')->nullable()->after('clues');

            $table->foreign('tipo_personal_id')->references('id')->on('tipos_personal');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('personal_clues', function (Blueprint $table) {
            $table->dropColumn('tipo_personal_id');
        });
    }
}
