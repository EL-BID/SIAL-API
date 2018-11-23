<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableAjustePedidoPresupuestoApartado extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ajuste_pedido_presupuesto_apartado', function (Blueprint $table) {
            $table->increments('id');
            $table->string('clues');
            $table->string('pedido_id');
            $table->string('almacen_id');
            $table->integer('mes');
            $table->integer('anio');
            $table->decimal('causes_comprometido',15,2)->default(0);
            $table->decimal('causes_devengado',15,2)->default(0);
            $table->decimal('no_causes_comprometido',15,2)->default(0);
            $table->decimal('no_causes_devengado',15,2)->default(0);
            $table->decimal('material_curacion_comprometido',15,2)->default(0);
            $table->decimal('material_curacion_devengado',15,2)->default(0);
            $table->string('usuario_id');
            $table->string('status',3)->default("P")->comment("P = Pendiente / ARL = Aplicado en remoto y local");
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
        Schema::drop('ajuste_pedido_presupuesto_apartado');
    }
}
