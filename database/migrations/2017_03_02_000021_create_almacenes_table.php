<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAlmacenesTable extends Migration{
    /**
     * Run the migrations.
     * @table almacenes
     *
     * @return void
     */
    public function up(){
       Schema::create('almacenes', function(Blueprint $table) {
          $table->engine = 'InnoDB';
      
          $table->string('id', 255);
          $table->integer('incremento');
          $table->string('servidor_id', 4);
          $table->string('nivel_almacen', 100)->comment('* 1 => HACE PEDIDOS AL PROVEEDOR\n 2 => HACE PEDIDOS A OTRO ALMACEN DENTRO DE LA CLUES');
          $table->string('tipo_almacen', 100)->comment('* ALMPAL\n* JURIS\n* UNMED\n* LAB\n* FARMACIA\n* CENDIS');
          $table->string('clues', 45);
          $table->boolean('subrogado');
          $table->integer('proveedor_id')->unsigned()->nullable();
          $table->boolean('unidosis');
          $table->string('nombre', 255);
          $table->string('encargado_almacen_id', 255)->nullable();

          $table->string('usuario_id', 255);
          
          $table->primary('id');
      
          //$table->index('proveedor_id','fk_almacenes_proveedores1_idx');
          $table->foreign('proveedor_id')->references('id')->on('proveedores');
          $table->foreign('encargado_almacen_id')->references('id')->on('personal_clues');

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
       Schema::dropIfExists('almacenes');
     }
}
