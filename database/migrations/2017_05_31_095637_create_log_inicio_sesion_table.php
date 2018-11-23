<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLogInicioSesionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('log_inicio_sesion', function (Blueprint $table) {
		    $table->string('servidor_id', 4);
            $table->string('usuario_id');
            $table->string('login_status',10);
            $table->string('ip',15);
            $table->string('navegador');
            $table->timestamp('updated_at');

		    $table->foreign('usuario_id')->references('id')->on('usuarios');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('log_inicio_sesion');
    }
}