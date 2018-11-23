<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CrearTablaSincronizacionMovimientos extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sincronizacion_movimientos', function (Blueprint $table) {
            
            $table->string('id',255);
            $table->integer('incremento');
            $table->string('servidor_id',4);

            $table->string('sincronizacion_proveedor_id', 255);
            $table->string('movimiento_id',255);
            $table->string('usuario_id', 255);
             
            $table->timestamps();
            $table->softDeletes();

            $table->primary('id');
            $table->foreign('movimiento_id')->references('id')->on('movimientos');
            $table->foreign('sincronizacion_proveedor_id')->references('id')->on('sincronizaciones_proveedores');
            
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
         Schema::dropIfExists('sincronizacion_movimientos');
    }
}
