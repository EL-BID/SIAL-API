<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateComunicacionContactosTable extends Migration{
    /**
     * Run the migrations.
     * @table comunicacion_contacto
     *
     * @return void
     */
    public function up(){
        Schema::create('comunicacion_contactos', function(Blueprint $table) {
		    $table->engine = 'InnoDB';
		
		    $table->increments('id');
            $table->string('tipo', 45);
		    $table->integer('contacto_id')->unsigned()->nullable();
            $table->integer('proveedor_id')->unsigned()->nullable();
		    $table->integer('medio_contacto_id')->unsigned();
		    $table->string('valor', 255);
		    $table->string('usuario_id', 255);
		/*
		    $table->index('medio_contacto_id','fk_datos_contacto_medios_contacto1_idx');
		    $table->index('contacto_id','fk_comunicacion_contacto_contactos1_idx');
		*/
		    $table->foreign('medio_contacto_id')->references('id')->on('medios_contacto');
		    $table->foreign('contacto_id')->references('id')->on('contactos');
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
       Schema::dropIfExists('comunicacion_contactos');
     }
}
