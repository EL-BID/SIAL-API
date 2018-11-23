<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCatalogoMunicipios extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('catalogo_municipios', function (Blueprint $table) {
            $table->increments('id');
            $table->string('clave', 10);
            $table->string('nombre', 30);
            $table->integer('jurisdiccion')->unsigned();
           
            
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
        Schema::dropIfExists('catalogo_municipios');
    }
}
