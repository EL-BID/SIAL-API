<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CrearTablaMovimientoSalidaMetadatosAlmacenGeneral extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('movimiento_salida_metadatos_ag', function (Blueprint $table)
        {

            $table->string('id',255);
            $table->integer('incremento');
            $table->string('servidor_id', 4);

            $table->string('movimiento_id', 255);
            $table->string('clues_destino',12)->nullable();
            $table->string('persona_entrega',255)->nullable();
            $table->string('persona_recibe',255);

            $table->string('usuario_id',255);
            $table->timestamps();
            $table->softDeletes();
            $table->primary('id');

            $table->foreign('movimiento_id')->references('id')->on('movimientos');
            $table->foreign('clues_destino')->references('clues')->on('unidades_medicas');
  
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
