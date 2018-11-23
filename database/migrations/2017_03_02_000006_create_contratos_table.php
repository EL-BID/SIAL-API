<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateContratosTable extends Migration{
    /**
     * Run the migrations.
     * @table contratos
     *
     * @return void
     */
    public function up(){
       Schema::create('contratos', function(Blueprint $table) {
          $table->engine = 'InnoDB';
      
          $table->increments('id');
          //$table->integer('proveedor_id')->unsigned();
          $table->decimal('monto_minimo', 15, 2)->nullable();
          $table->decimal('monto_maximo', 15, 2)->nullable();
          $table->date('fecha_inicio');
          $table->date('fecha_fin');
          $table->boolean('activo')->default(0);
          
          $table->integer('usuario_id');
      
          //$table->index('proveedor_id','fk_contratos_proveedores1_idx');
          //$table->index('extension_contrato_id','fk_contratos_extensiones_contratos1_idx');
      
          //$table->foreign('proveedor_id')->references('id')->on('proveedores');
      
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
       Schema::dropIfExists('contratos');
     }
}
