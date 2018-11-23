<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProveedoresTable extends Migration{
    /**
     * Run the migrations.
     * @table proveedores
     *
     * @return void
     */
    public function up(){
        Schema::create('proveedores', function(Blueprint $table) {
            $table->engine = 'InnoDB';
        
            $table->increments('id');
            $table->string('nombre', 255);
            $table->string('rfc', 45)->nullable();
            $table->string('direccion', 255)->nullable();
            $table->string('ciudad', 255)->nullable();
            $table->string('contacto',255)->nullable();
            $table->string('cargo_contacto',255)->nullable();
            $table->string('telefono',20)->nullable();
            $table->string('celular',20)->nullable();
            $table->string('email',255)->nullable();
            
            $table->boolean('activo');
            $table->string('usuario_id', 255);
            //$table->unique('rfc','rfc_UNIQUE');
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
       Schema::dropIfExists('proveedores');
     }
}
