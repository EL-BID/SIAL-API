<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePedidoProveedorInsumosTable extends Migration{
    /**
     * Run the migrations.
     * @table pedido_proveedor_insumos
     *
     * @return void
     */
    public function up(){
        Schema::create('pedido_proveedor_insumos', function(Blueprint $table) {
		    $table->engine = 'InnoDB';
		
		    $table->string('id', 255);
		    $table->integer('incremento');
		    $table->string('servidor_id', 4);
		    $table->string('pedido_id', 255);
		    $table->string('pedido_insumo_id', 255);
		    $table->integer('proveedor_id')->unsigned();
		    $table->integer('cantidad');
		    $table->string('usuario_id', 255);
		    
		    $table->primary('id');
		/*
		    $table->index('pedido_id','fk_pedido_proveedor_insumos_pedidos1_idx');
		    $table->index('pedido_insumo_id','fk_pedido_proveedor_insumos_pedidos_insumos1_idx');
		    $table->index('proveedor_id','fk_pedido_proveedor_insumos_proveedores1_idx');
		*/
		    $table->foreign('pedido_id')->references('id')->on('pedidos');
		    $table->foreign('pedido_insumo_id')->references('id')->on('pedidos_insumos');
		    $table->foreign('proveedor_id')->references('id')->on('proveedores');
		
		    $table->timestamps();
			$table->softDeletes();
		});
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
     public function down(){
       Schema::dropIfExists('pedido_proveedor_insumos');
     }
}
