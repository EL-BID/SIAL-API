<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CrearTablaMetadatosPedidoCompraConsolidada extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pedido_metadatos_cc', function(Blueprint $table) {
		    $table->engine = 'InnoDB';
		
		    $table->string('id', 255);
		    $table->integer('incremento');
		    $table->string('servidor_id', 255);
		    $table->string('pedido_id', 255);
            $table->integer('programa_id')->unsigned()->nullable();
            $table->date('fecha_limite_captura');
            $table->string('lugar_entrega', 255);
            $table->decimal('presupuesto_compra',15,2)->default(0)->nullable();
            $table->decimal('presupuesto_causes',15,2)->default(0)->nullable();
            $table->decimal('presupuesto_no_causes',15,2)->default(0)->nullable();
            $table->decimal('presupuesto_causes_asignado',15,2)->default(0)->nullable();
            $table->decimal('presupuesto_causes_disponible',15,2)->default(0);
            $table->decimal('presupuesto_no_causes_asignado',15,2)->default(0);
            $table->decimal('presupuesto_no_causes_disponible',15,2)->default(0);
		    
		    $table->string('usuario_id', 255);
		
		    $table->timestamps();
            $table->softDeletes();
            $table->primary('id');
            
            $table->foreign('pedido_id')->references('id')->on('pedidos');
            $table->foreign('programa_id')->references('id')->on('programas');
		
		});

        
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
                Schema::drop('pedido_metadatos_cc');

             
    }
}
