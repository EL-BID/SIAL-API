<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CrearTablaArticulos extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('articulos', function (Blueprint $table) {
            
            $table->increments('id');

            $table->integer('categoria_id')->unsigned();
            $table->integer('articulo_id')->unsigned()->nullable();
            $table->string('nombre',255);
            $table->string('descripcion',255);
            $table->boolean('es_activo_fijo',1);

            $table->string('usuario_id',255);
            $table->timestamps();
            $table->softDeletes();
 
            $table->foreign('categoria_id')->references('id')->on('categorias');
            $table->foreign('articulo_id')->references('id')->on('articulos');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('articulos');
    }
}
