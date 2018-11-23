<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CrearTablaDocumentosSistemaCargosV1 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
  
    Schema::create('documentos_sistema_cargos', function (Blueprint $table){

            $table->increments('id');
            $table->integer('documento_sistema_id')->unsigned();
            $table->string('leyenda',255);
            $table->integer('cargo_id')->unsigned();

            $table->string('usuario_id',255);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('documento_sistema_id')->references('id')->on('documentos_sistema');
            $table->foreign('cargo_id')->references('id')->on('cargos');
 
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
