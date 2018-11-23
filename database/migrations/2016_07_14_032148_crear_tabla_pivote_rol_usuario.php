<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CrearTablaPivoteRolUsuario extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('rol_usuario', function (Blueprint $table) {
            		
			$table->integer('rol_id')->unsigned();
            $table->string('usuario_id');

			$table->foreign('rol_id')
                  ->references('id')->on('roles')
                  ->onDelete('cascade');

            $table->foreign('usuario_id')
                  ->references('id')->on('usuarios')
                  ->onUpdate('cascade')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('rol_usuario');
    }
}
