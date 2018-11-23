<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLogPedidosCanceladosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('log_pedidos_cancelados', function (Blueprint $table) {
            $table->string('servidor_id', 4);
            $table->string('pedido_id');
            $table->string('usuario_id');

            $table->decimal('total_monto_restante',15,2)->default(0);
            $table->integer('mes_destino');
            $table->integer('anio_destino');
            
            $table->string('ip',15);
            $table->string('navegador');
            $table->timestamp('updated_at');
            
		    $table->foreign('pedido_id')->references('id')->on('pedidos');
            $table->foreign('usuario_id')->references('id')->on('usuarios');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('log_pedidos_cancelados');
    }
}
