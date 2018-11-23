<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CrearTablaPedidosInsumosClues extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pedidos_insumos_clues', function (Blueprint $table) {
            $table->string('id', 255);
            $table->integer('incremento');
            $table->string('servidor_id', 4);

            $table->string('pedido_insumo_id', 255);
            
            $table->integer('cantidad')->nullable();
            $table->string('clues', 45);
            $table->string('usuario_id', 255);
            
            $table->primary('id');
        
        
            $table->foreign('pedido_insumo_id')->references('id')->on('pedidos_insumos')->onDelete('cascade')->onUpdate('cascade');
        
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
        Schema::drop('pedidos_insumos_clues');
    }
}
