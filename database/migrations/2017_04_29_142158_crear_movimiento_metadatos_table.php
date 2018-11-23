<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CrearMovimientoMetadatosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('movimiento_metadatos', function (Blueprint $table) {
            $table->string('id');
            $table->integer('incremento');
            $table->string('servidor_id',4);
            $table->string('movimiento_id');
            $table->integer('servicio_id')->unsigned();
            $table->integer('turno_id')->unsigned();
            $table->string('persona_recibe');
            $table->string('usuario_id', 255);
            
            $table->primary('id');
        
            $table->foreign('movimiento_id')->references('id')->on('movimientos');
            $table->foreign('servicio_id')->references('id')->on('servicios');
            $table->foreign('turno_id')->references('id')->on('turnos');

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
        Schema::drop('movimiento_metadatos');
    }
}
