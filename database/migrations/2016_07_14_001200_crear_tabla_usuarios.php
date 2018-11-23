<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CrearTablaUsuarios extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('usuarios', function (Blueprint $table) {
            $table->string('id');               
            $table->string('servidor_id',4);
            $table->string('password',60);
            $table->string('nombre');
            $table->string('apellidos');
            $table->string('avatar');
            $table->boolean('su')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->primary('id');
            $table->foreign('servidor_id')->references('id')->on('servidores')->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('usuarios');
    }
}
