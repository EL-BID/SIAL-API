<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePedidoPresupuestoApartadoTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pedido_presupuesto_apartado', function (Blueprint $table) {
            $table->increments('id');
            $table->string('clues', 12);
            $table->string('pedido_id');
            $table->string('almacen_id');
            $table->integer('mes');
            
            $table->decimal('causes_comprometido',15,2)->default(0);
            $table->decimal('causes_devengado',15,2)->default(0);

            $table->decimal('no_causes_comprometido',15,2)->default(0);
            $table->decimal('no_causes_devengado',15,2)->default(0);

            $table->decimal('material_curacion_comprometido',15,2)->default(0);
            $table->decimal('material_curacion_devengado',15,2)->default(0);
            
            $table->foreign('pedido_id')->references('id')->on('pedidos');
            $table->foreign('almacen_id')->references('id')->on('almacenes');
            $table->foreign('clues')->references('clues')->on('unidades_medicas');

            $table->string('usuario_id', 255);
            
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
        Schema::drop('pedido_presupuesto_apartado');
    }
}
