<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCatalogoLocalidades extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('catalogo_localidades', function (Blueprint $table) {
            $table->increments('id');
            $table->string('clave', 10);
            $table->string('nombre', 70);
            $table->decimal('numeroLatitud', 10,8);
            $table->decimal('numeroLongitud', 10,8);
            $table->decimal('numeroAltitud', 10,2);
            $table->string('claveCarta', 6);
            $table->integer('municipio_id')->unsigned();
            $table->string('claveMunicipio',4);

            $table->foreign('municipio_id')->references('id')->on('catalogo_municipios');
            
            
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
        Schema::dropIfExists('catalogo_localidades');
    }
}
