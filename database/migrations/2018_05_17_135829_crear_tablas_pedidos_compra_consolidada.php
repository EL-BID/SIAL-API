<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CrearTablasPedidosCompraConsolidada extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pedido_cc_clues', function(Blueprint $table) {
		    $table->engine = 'InnoDB';
		
		    $table->string('id', 255);
		    $table->integer('incremento');
		    $table->string('servidor_id', 255);
		    $table->string('pedido_id', 255);
            $table->string('clues', 45);
            $table->string('estatus', 25);
            $table->decimal('presupuesto_clues',15,2)->default(0);
            $table->decimal('presupuesto_causes',15,2)->default(0)->nullable();
            $table->decimal('presupuesto_no_causes',15,2)->default(0)->nullable();
            $table->decimal('presupuesto_causes_asignado',15,2)->default(0);
            $table->decimal('presupuesto_causes_disponible',15,2)->default(0);
            $table->decimal('presupuesto_no_causes_asignado',15,2)->default(0);
            $table->decimal('presupuesto_no_causes_disponible',15,2)->default(0);
		    
		    $table->string('usuario_id', 255);
		
		    $table->timestamps();
            $table->softDeletes();

            $table->primary('id');
            $table->foreign('pedido_id')->references('id')->on('pedidos');

            
		
		});

        Schema::table('pedidos_insumos', function (Blueprint $table) {
            $table->boolean('ajustado')->default(0)->after('cantidad_solicitada');;
        });


    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('pedido_cc_clues');
        Schema::table('pedidos_insumos', function (Blueprint $table) {
            $table->dropColumn('ajustado');
        });

    }
}
