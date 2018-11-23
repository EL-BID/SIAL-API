<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CrearTablaAvancesUsuarios extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('avance_usuario_privilegio', function (Blueprint $table) {
            $table->string('id', 255);
            $table->integer('incremento');
            $table->string('servidor_id', 4);

            $table->string('usuario_id', 255);
            $table->string('avance_id', 255);

            $table->string('agregar', 255);
            $table->string('editar', 255);
            $table->string('eliminar', 255);
            
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('avance_id')->references('id')->on('avances');
            $table->foreign('usuario_id')->references('id')->on('usuarios');
            $table->primary('id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('avance_usuario_privilegio');
    }
}
