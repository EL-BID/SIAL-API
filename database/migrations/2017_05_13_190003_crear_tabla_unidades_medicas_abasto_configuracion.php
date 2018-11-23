<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CrearTablaUnidadesMedicasAbastoConfiguracion extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('unidad_medica_abasto_configuracion', function (Blueprint $table) {
            $table->increments('id');
            $table->string('clues', 12);
            
            $table->integer('cantidad_causes');
            $table->integer('cantidad_no_causes');
            $table->integer('cantidad_material_curacion');
            
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('clues')->references('clues')->on('unidades_medicas');
            
           
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('unidad_medica_abasto_configuracion');
    }
}
