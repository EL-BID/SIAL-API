<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CrearTablaInventarioMetadatos extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('inventario_metadatos', function (Blueprint $table) {
            
            $table->string('id', 255);
            $table->string('servidor_id', 255);
            $table->integer('incremento');

            $table->string('inventario_id',255);
            $table->integer('metadatos_id')->unsigned();
            $table->string('campo',100);
            $table->text('valor');
  
            $table->string('usuario_id',255);
            $table->timestamps();
            $table->softDeletes();
 
            $table->primary('id');
            $table->foreign('inventario_id')->references('id')->on('inventario');
            $table->foreign('metadatos_id')->references('id')->on('categorias_metadatos');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('inventario_metadatos');
    }
}
