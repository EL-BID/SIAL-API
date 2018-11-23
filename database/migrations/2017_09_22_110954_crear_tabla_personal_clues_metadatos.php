<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CrearTablaPersonalCluesMetadatos extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('personal_clues_metadatos', function (Blueprint $table) {
            
            $table->string('id',255);
            $table->integer('incremento');
            $table->string('servidor_id',4);

            $table->string('personal_clues_id', 255);
            $table->string('campo',50);
            $table->string('valor',255);
            $table->string('usuario_id', 255);

             
            $table->timestamps();
            $table->softDeletes();

            $table->primary('id');
            $table->foreign('personal_clues_id')->references('id')->on('personal_clues');

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
