<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CrearTablaMovimientoArticulos extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('movimiento_articulos', function (Blueprint $table) {
            
            $table->string('id', 255);
            $table->string('servidor_id', 255);
            $table->integer('incremento');

            $table->string('movimiento_id');
            $table->integer('articulo_id')->unsigned();
            $table->decimal('cantidad',16,2);
            $table->decimal('precio_unitario',16,2);
            $table->decimal('iva',16,2);
            $table->decimal('importe',16,2);
             $table->string('observaciones',255);
  
            $table->string('usuario_id',255);
            $table->timestamps();
            $table->softDeletes();
 
            $table->primary('id');
            $table->foreign('movimiento_id')->references('id')->on('movimientos');
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
        Schema::dropIfExists('movimiento_articulos');
    }
}
