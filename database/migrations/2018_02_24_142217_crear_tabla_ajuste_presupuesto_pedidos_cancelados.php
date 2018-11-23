<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CrearTablaAjustePresupuestoPedidosCancelados extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ajuste_presupuesto_pedidos_cancelados', function (Blueprint $table) {
            
        
            $table->string('id', 255);
		    $table->integer('incremento');
            $table->string('servidor_id', 4);
            
            $table->string('pedido_id');
            $table->integer('unidad_medica_presupuesto_id')->unsigned();

            $table->string('clues', 12);
            $table->integer('mes_origen');
            $table->integer('anio_origen');
            $table->integer('mes_destino');
            $table->integer('anio_destino');

            $table->decimal('causes',15,2)->default(0);
            $table->decimal('no_causes',15,2)->default(0);
            $table->decimal('material_curacion',15,2)->default(0);
            $table->decimal('insumos',15,2)->default(0);    
            
            $table->string('status',3)->default("P")->comment("P = Pendiente / AR = Aplicado en Remoto / ARL = Aplicado en remoto y local");

            $table->string('usuario_id', 255);
            $table->timestamps();
            $table->softDeletes();

            $table->primary('id');

            $table->foreign('unidad_medica_presupuesto_id','ajuste_presupuesto_ped_can_um_foreign')->references('id')->on('unidad_medica_presupuesto');
            $table->foreign('clues')->references('clues')->on('unidades_medicas');
            $table->foreign('servidor_id')->references('id')->on('servidores');
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
        Schema::drop('ajuste_presupuesto_pedidos_cancelados');
    }
}
