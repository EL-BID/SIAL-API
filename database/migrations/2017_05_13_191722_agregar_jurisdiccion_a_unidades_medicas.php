<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AgregarJurisdiccionAUnidadesMedicas extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('unidades_medicas', function (Blueprint $table) {
            //
            $table->integer('jurisdiccion_id')->unsigned()->nullable()->after('clues');;
            $table->foreign('jurisdiccion_id')->references('id')->on('jurisdicciones');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('unidades_medicas', function (Blueprint $table) {
            $table->dropForeign(['jurisdiccion_id']);
            $table->dropColumn('jurisdiccion_id');
        });
    }
}
