<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CrearTablaClavesBasicas extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('claves_basicas', function (Blueprint $table) {
            
            $table->string('id', 255);
		    $table->integer('incremento');
		    $table->string('servidor_id', 4);
            $table->string('clues', 45);
            $table->string('nombre');
            $table->string('tipo', 3)->nullable()->default('CA')->comment('CA = Causes\nNCA = No causes\nMC = Material de curación');  
            $table->string('usuario_id', 255);
            
            $table->timestamps();
			$table->softDeletes();

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
        Schema::drop('claves_basicas');
    }
}
