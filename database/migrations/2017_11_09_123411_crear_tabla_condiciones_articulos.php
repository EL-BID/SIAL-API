<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CrearTablaCondicionesArticulos extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('condiciones_articulos', function (Blueprint $table) {
            
            $table->increments('id');

            $table->string('nombre',255);

            $table->string('usuario_id',255);
            $table->timestamps();
            $table->softDeletes();
 
         });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('condiciones_articulos');
    }
}
