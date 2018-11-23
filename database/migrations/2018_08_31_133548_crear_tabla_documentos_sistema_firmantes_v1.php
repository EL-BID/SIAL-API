<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CrearTablaDocumentosSistemaFirmantesV1 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('documentos_sistema_firmantes', function (Blueprint $table){

            $table->string('id',255);
            $table->integer('incremento');
            $table->string('servidor_id', 4);

            $table->string('almacen_id', 255);
            $table->integer('documento_sistema_cargo_id')->unsigned();
            $table->string('nombre',255);

            $table->string('usuario_id',255);
            $table->timestamps();
            $table->softDeletes();
            $table->primary('id');

            $table->foreign('almacen_id')->references('id')->on('almacenes');
            $table->foreign('documento_sistema_cargo_id')->references('id')->on('documentos_sistema_cargos');
 
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
