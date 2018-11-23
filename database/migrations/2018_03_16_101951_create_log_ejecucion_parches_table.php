<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLogEjecucionParchesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('log_ejecucion_parches', function (Blueprint $table) {
            $table->string('id', 255);
		    $table->integer('incremento');
            $table->string('servidor_id', 4);
            
            $table->string('clues', 12)->nullable();
            $table->string('tipo_parche',10);
            $table->string('nombre_parche',255);
            $table->date('fecha_liberacion');
            $table->timestamp('fecha_ejecucion')->nullable();

            $table->string('usuario_id', 255);
            $table->timestamps();
            $table->softDeletes();

            $table->primary('id');

            $table->foreign('clues')->references('clues')->on('unidades_medicas');
            $table->foreign('servidor_id')->references('id')->on('servidores');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('log_ejecucion_parches');
    }
}
