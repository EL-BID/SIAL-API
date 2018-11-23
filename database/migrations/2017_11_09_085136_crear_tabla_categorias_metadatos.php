<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CrearTablaCategoriasMetadatos extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('categorias_metadatos', function (Blueprint $table) {
            
            $table->increments('id');

            $table->integer('categoria_id')->unsigned();
            $table->string('campo',100);
            $table->string('descripcion',255);
            $table->string('tipo',55);
            $table->integer('longitud');
            $table->boolean('requerido',1);
            $table->boolean('requerido_inventario',1);

            $table->string('usuario_id',255);
            $table->timestamps();
            $table->softDeletes();
 
            $table->foreign('categoria_id')->references('id')->on('categorias');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('categorias_metadatos');
    }
}
