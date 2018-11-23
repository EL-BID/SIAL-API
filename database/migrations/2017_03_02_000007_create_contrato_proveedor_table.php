<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateContratoProveedorTable extends Migration{
    /**
     * Run the migrations.
     * @table clues_servicios
     *
     * @return void
     */
    public function up(){
        Schema::create('contrato_proveedor', function(Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->integer('contrato_id')->unsigned();
            $table->integer('proveedor_id')->unsigned();

            $table->foreign('proveedor_id')->references('id')->on('proveedores');
            $table->foreign('contrato_id')->references('id')->on('contratos');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
     public function down(){
       Schema::dropIfExists('contrato_proveedor');
     }
}
