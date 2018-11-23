<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateContratosPreciosTable extends Migration{
    /**
     * Run the migrations.
     * @table contratos_precios
     *
     * @return void
     */
    public function up(){
        Schema::create('contratos_precios', function(Blueprint $table) {
            $table->engine = 'InnoDB';
        
            $table->increments('id');
            $table->integer('contrato_id')->unsigned();
            $table->integer('contrato_pedido_id')->unsigned()->nullable();
            $table->integer('proveedor_id')->unsigned();
            $table->string('lote', 45);
            $table->string('insumo_medico_clave', 255);
            //$table->string('marca', 100)->nullable();
            $table->decimal('precio', 15, 2);
            $table->string('usuario_id', 255);
            
            $table->foreign('contrato_id')->references('id')->on('contratos');
            $table->foreign('proveedor_id')->references('id')->on('proveedores');
            $table->foreign('contrato_pedido_id')->references('id')->on('contratos_pedidos');
        
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
       Schema::dropIfExists('contratos_precios');
     }
}
