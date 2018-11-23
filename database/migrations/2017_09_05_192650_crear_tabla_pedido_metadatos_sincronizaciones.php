<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CrearTablaPedidoMetadatosSincronizaciones extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pedido_metadatos_sincronizaciones', function (Blueprint $table) {
            
            $table->string('id',255);
            $table->integer('incremento');
            $table->string('servidor_id',4);

            $table->string('pedido_id', 255);
            $table->integer('total_recetas');
            $table->integer('total_colectivos');
            $table->integer('total_recetas_repetidas');
            $table->integer('total_colectivos_repetidos');
             
            $table->timestamps();
            $table->softDeletes();

            $table->primary('id');
            $table->foreign('pedido_id')->references('id')->on('pedidos');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pedido_metadatos_sincronizaciones');
    }
}
