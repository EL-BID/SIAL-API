<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLogRepositorioProveedor extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('log_repositorio', function (Blueprint $table) {
            $table->string('id', 255);
            $table->integer('incremento');
            $table->string('servidor_id', 4);
            $table->string('repositorio_id', 255);
            $table->string('usuario_id', 255);
            
            $table->string('ip', 15);
            $table->string('navegador', 255);
            $table->string('accion', 255);
            
            $table->foreign('repositorio_id')->references('id')->on('repositorio');
            $table->primary('id');
            
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
        Schema::dropIfExists('repositorio_log');
    }
}
