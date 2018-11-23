<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAlmacenUsuariosTable extends Migration{
    /**
     * Run the migrations.
     * @table pedido_proveedor_insumos
     *
     * @return void
     */
    public function up(){
        Schema::create('almacen_usuario', function(Blueprint $table) {
            $table->engine = 'InnoDB';
            
            $table->increments('id');
            //$table->string('id', 255);
            //$table->integer('incremento');
            //$table->string('servidor_id', 4);
            $table->string('usuario_id', 255);
            $table->string('almacen_id', 255);
            //$table->string('usuario_id', 255);
            
            //$table->primary('id');
      
            $table->foreign('usuario_id')->references('id')->on('usuarios');
            $table->foreign('almacen_id')->references('id')->on('almacenes');
      
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
       Schema::dropIfExists('almacen_usuario');
     }
}
