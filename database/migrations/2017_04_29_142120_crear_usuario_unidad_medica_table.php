<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CrearUsuarioUnidadMedicaTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('usuario_unidad_medica', function (Blueprint $table) {
            $table->increments('id');
            $table->string('usuario_id');
			$table->string('clues',12);
            $table->timestamps();
            $table->softDeletes();


			$table->foreign('usuario_id')
                  ->references('id')->on('usuarios')
                  ->onDelete('cascade');

			$table->foreign('clues')
                  ->references('clues')->on('unidades_medicas')
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
        Schema::drop('usuario_unidad_medica');
    }
}
