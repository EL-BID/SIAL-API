<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLogSync extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('log_sync', function (Blueprint $table) {
            $table->string('id', 255);
		    $table->integer('incremento');
            $table->string('servidor_id', 4);
            
            $table->string('clues', 12)->nullable();
            $table->string('descripcion',255);           
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
        Schema::drop('log_sync');
    }
}
