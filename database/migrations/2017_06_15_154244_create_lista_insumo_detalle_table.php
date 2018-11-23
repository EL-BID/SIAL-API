<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateListaInsumoDetalleTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('lista_insumo_detalle', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('lista_insumo_id')->unsigned();
            $table->string('insumo_medico_clave', 255);            
            
            $table->foreign('lista_insumo_id')->references('id')->on('listas_insumos');
            $table->foreign('insumo_medico_clave')->references('clave')->on('insumos_medicos')->onUpdate('cascade');
         
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('lista_insumo_detalle');
    }
}
