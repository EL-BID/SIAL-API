<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CrearTablaTiposPersonalMetadatos extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tipos_personal_metadatos', function(Blueprint $table) {

            $table->integer('id');
            $table->integer('tipo_personal_id');
            $table->string('campo', 55);
            $table->string('tipo');
            $table->integer('longitud');
        
            $table->primary('id');
            $table->foreign('tipo_personal_id')->references('id')->on('tipos_personal');

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
        Schema::dropIfExists('tipos_personal_metadatos');
    }
}
