<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CrearTablaResguardos extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('resguardos', function (Blueprint $table) {
            
            $table->string('id', 255);
            $table->string('servidor_id', 255);
            $table->integer('incremento');
            $table->string('almacen_id', 255);
            $table->date('fecha_resguardo');
            $table->string('personal_clues_id', 255);
            $table->string('observaciones',255);
  
            $table->string('usuario_id',255);
            $table->timestamps();
            $table->softDeletes();
 
            $table->primary('id');
            $table->foreign('personal_clues_id')->references('id')->on('personal_clues');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
                Schema::dropIfExists('resguardos');
    }
}
