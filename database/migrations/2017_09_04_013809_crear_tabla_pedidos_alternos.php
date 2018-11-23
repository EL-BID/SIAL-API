<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CrearTablaPedidosAlternos extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pedidos_alternos', function (Blueprint $table) {
            
            $table->string('id', 255);
		    $table->integer('incremento');
            $table->string('servidor_id', 4);
            
            $table->string('pedido_id');
            $table->string('pedido_original_id')->nullable();
            $table->string('folio')->nullable();
            $table->string('nombre_firma_1', 255)->nullable();
            $table->string('cargo_firma_1', 255)->nullable();
            $table->string('nombre_firma_2', 255)->nullable();
            $table->string('cargo_firma_2', 255)->nullable();
            
            $table->string('usuario_valido_id', 255)->nullable();
            $table->string('usuario_asigno_proveedor_id', 255)->nullable();
            
            $table->datetime('fecha_validacion')->nullable();
            $table->datetime('fecha_asignacion_proveedor')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->primary('id');
            $table->foreign('pedido_id')->references('id')->on('pedidos');
            $table->foreign('pedido_original_id')->references('id')->on('pedidos');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('pedidos_alternos');
    }
}
