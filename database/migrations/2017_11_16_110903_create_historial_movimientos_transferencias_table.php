<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHistorialMovimientosTransferenciasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('historial_movimientos_transferencias', function (Blueprint $table) {
            $table->string('id', 255);
            $table->string('servidor_id', 255);
            $table->integer('incremento');

            $table->string('almacen_origen', 255);
            $table->string('almacen_destino', 255);

            $table->string('clues_origen',45);
            $table->string('clues_destino',45);

            $table->string('pedido_id',255)->nullable();

            $table->string('evento',150);

            $table->string('movimiento_id',255)->nullable();

            $table->integer('total_unidades');
            $table->integer('total_claves');
            $table->decimal('total_monto',15,2)->default(0);

            $table->timestamp('fecha_inicio_captura');
            $table->timestamp('fecha_finalizacion')->nullable();

            $table->string('usuario_id',255);

            $table->timestamps();
            $table->softDeletes();
            
            $table->primary('id');
            $table->foreign('almacen_origen')->references('id')->on('almacenes');
            $table->foreign('almacen_destino')->references('id')->on('almacenes');
            $table->foreign('clues_origen')->references('clues')->on('unidades_medicas');
            $table->foreign('clues_destino')->references('clues')->on('unidades_medicas');
            $table->foreign('pedido_id')->references('id')->on('pedidos');
            $table->foreign('movimiento_id')->references('id')->on('movimientos');
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
        Schema::drop('historial_movimientos_transferencias');
    }
}
