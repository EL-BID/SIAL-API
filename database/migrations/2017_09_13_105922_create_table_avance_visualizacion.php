<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableAvanceVisualizacion extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('avance_visualizacion', function (Blueprint $table) {
            
            $table->string('id',255);
            $table->integer('incremento');
            $table->string('servidor_id',4);

            $table->string('avance_id', 255);
            $table->string('usuario_id', 255);
             
            $table->timestamps();
            $table->softDeletes();

            
            $table->foreign('avance_id')->references('id')->on('avances');
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
        Schema::dropIfExists('avance_visualizacion');
    }
}
