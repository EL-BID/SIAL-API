<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTablaSustanciasLaboratorioAddTipo extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sustancias_laboratorio', function (Blueprint $table) {

            $table->integer('tipo_sustancia_id')->unsigned()->after('insumo_medico_clave');
            $table->foreign('tipo_sustancia_id')->references('id')->on('tipos_sustancias');

         });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('sustancias_laboratorio', function (Blueprint $table) {

            $table->dropForeign(['tipo_sustancia_id']);
            $table->dropColumn('tipo_sustancia_id');
            
         });
    }
}
