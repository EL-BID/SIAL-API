<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSysLogsTable extends Migration
{
    /**
     * Run the migrations.
     * @table sys_logs
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sys_logs', function(Blueprint $table) {
		    $table->engine = 'InnoDB';
		
		    $table->string('id', 255);
		    $table->string('servidor_id', 4);
		    $table->string('usuarios_id',255);
		    $table->string('ip', 19);
		    $table->string('mac', 19);
		    $table->string('tipo', 6);
		    $table->string('ruta', 50);
		    $table->string('controlador', 45);
		    $table->string('tabla', 25);
		    $table->text('peticion');
		    $table->text('respuesta');
		    $table->text('info');
		    
		    $table->primary('id');
		
		    $table->timestamps();
		
		});
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
     public function down()
     {
       Schema::dropIfExists('sys_logs');
     }
}
