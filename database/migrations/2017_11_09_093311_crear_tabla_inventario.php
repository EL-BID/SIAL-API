<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CrearTablaInventario extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('inventario', function (Blueprint $table) {
            
            $table->string('id', 255);
            $table->string('servidor_id', 255);
            $table->integer('incremento');
            $table->string('almacen_id', 255);
            $table->integer('articulo_id')->unsigned();
            $table->string('movimiento_articulo_id')->nullable();
            $table->string('numero_inventario', 55);
            $table->decimal('existencia', 16,2);
            $table->string('observaciones',255);
            $table->boolean('baja',1);
 
            $table->string('usuario_id',255);
            $table->timestamps();
            $table->softDeletes();
 
            $table->primary('id');
            $table->foreign('articulo_id')->references('id')->on('articulos');
            $table->foreign('movimiento_articulo_id')->references('id')->on('movimiento_articulos');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('inventario');
    }
}
