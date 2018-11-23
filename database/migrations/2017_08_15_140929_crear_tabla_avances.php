<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CrearTablaAvances extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('avances', function (Blueprint $table) {
            $table->string('id', 255);
            $table->integer('incremento');
            $table->string('servidor_id', 4);
            $table->string('tema', 255);
            $table->string('responsable', 255);
            $table->string('area', 255);
            $table->text('comentario');
            
            $table->string('usuario_id', 255);
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
        Schema::drop('avances');
    }
}
