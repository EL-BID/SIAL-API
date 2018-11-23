<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CrearTablaResguardoArticulos extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('resguardo_articulos', function (Blueprint $table)
        {

            $table->string('id',255);
            $table->integer('incremento');
            $table->string('servidor_id', 4);

            $table->string('resguardos_id', 255);
            $table->string('inventario_id', 255);
            $table->integer('condiciones_articulos_id')->unsigned()->nullable();
            $table->string('status', 55);

            $table->string('usuario_id',255);
            $table->timestamps();
            $table->softDeletes();
            $table->primary('id');

            $table->foreign('resguardos_id')->references('id')->on('resguardos');
            $table->foreign('inventario_id')->references('id')->on('inventario');
            $table->foreign('condiciones_articulos_id')->references('id')->on('condiciones_articulos');
  
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
