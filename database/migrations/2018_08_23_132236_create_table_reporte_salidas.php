<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableReporteSalidas extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('reporte_salidas', function(Blueprint $table) {
		    $table->engine = 'InnoDB';
		
		    $table->increments('id');
		    $table->string('clues', 255);
            $table->string('clave', 255);
            $table->integer('turno_id');
            $table->integer('servicio_id');
            $table->integer('mes');
            $table->integer('anio');
            $table->decimal('surtido', 15,2);
            $table->decimal('negado', 15,2);
            
            
		    $table->timestamps();
            $table->softDeletes();

            $table->foreign('clues')->references('clues')->on('unidades_medicas');
            $table->foreign('clave')->references('clave')->on('insumos_medicos');
		
		});
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('reporte_salidas');
    }
}
