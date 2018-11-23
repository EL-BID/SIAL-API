<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CrearTablaAvancesDetalles extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('avance_detalles', function (Blueprint $table) {
            $table->string('id',255);
            $table->integer('incremento');
            $table->string('servidor_id', 4);
            
            $table->string('avance_id', 255);
            $table->string('nombre', 255);
            $table->string('extension', 255);
            $table->text('comentario');
            $table->decimal('porcentaje', 15,2)->default(0);;
            
            $table->string('usuario_id', 255);
            
            $table->timestamps();
            $table->softDeletes();
            $table->primary('id');
            $table->foreign('avance_id')->references('id')->on('avances');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('avance_detalles');
    }
}
