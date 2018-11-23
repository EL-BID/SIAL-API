<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CrearTablaResguardosArticulosDevoluciones extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('resguardos_articulos_devoluciones', function (Blueprint $table)
        {

            $table->string('id',255);
            $table->integer('incremento');
            $table->string('servidor_id', 4);

            $table->string('resguardo_articulos_id', 255);
            $table->string('persona_recibe', 255);

            $table->string('usuario_id',255);
            $table->timestamps();
            $table->softDeletes();
            $table->primary('id');

            $table->foreign('resguardo_articulos_id')->references('id')->on('resguardo_articulos');
  
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
