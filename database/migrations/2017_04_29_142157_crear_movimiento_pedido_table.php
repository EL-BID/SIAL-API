<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CrearMovimientoPedidoTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('movimiento_pedido', function (Blueprint $table) {
            $table->string('id');
            $table->integer('incremento');
            $table->string('servidor_id',4);
            $table->string('movimiento_id');
            $table->string('pedido_id');
            $table->string('recibe');
            $table->string('entrega');
            $table->string('usuario_id', 255);
            
            $table->primary('id');
        
            $table->foreign('movimiento_id')->references('id')->on('movimientos');
            $table->foreign('pedido_id')->references('id')->on('pedidos');

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
        Schema::drop('movimiento_pedido');
    }
}
