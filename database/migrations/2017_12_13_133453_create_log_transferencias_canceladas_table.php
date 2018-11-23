<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLogTransferenciasCanceladasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('log_transferencias_canceladas', function (Blueprint $table) {
            $table->string('servidor_id', 4);
            $table->string('pedido_id');
            $table->string('usuario_id');

            $table->string('motivos');
            
            $table->string('ip',15);
            $table->string('navegador');
            $table->timestamp('updated_at');
            
		    $table->foreign('pedido_id')->references('id')->on('pedidos');
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
        Schema::drop('log_transferencias_canceladas');
    }
}
