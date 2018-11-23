<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTablaClavesBasicas extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::drop('claves_basicas_detalles');
        Schema::drop('claves_basicas');

        Schema::create('claves_basicas', function (Blueprint $table) {
            
            $table->increments('id');
            $table->string('nombre');
            $table->string('usuario_id', 255);
            
            $table->timestamps();
			$table->softDeletes();

        });


        Schema::create('claves_basicas_detalles', function (Blueprint $table) {
            $table->increments('id');
            
            $table->integer('claves_basicas_id')->unsigned();
            $table->string('insumo_medico_clave', 255);            
            $table->string('usuario_id', 255);
            
            
            $table->foreign('claves_basicas_id')->references('id')->on('claves_basicas');
            $table->foreign('insumo_medico_clave')->references('clave')->on('insumos_medicos')->onUpdate('cascade');
         
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('claves_basicas_unidades_medicas', function (Blueprint $table) {
            $table->increments('id');
            
            $table->integer('claves_basicas_id')->unsigned();
            $table->string('clues', 45);       
            $table->string('usuario_id', 255);
            
            
            $table->foreign('claves_basicas_id')->references('id')->on('claves_basicas');
            $table->foreign('clues')->references('clues')->on('unidades_medicas');
         
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
        
    }
}
