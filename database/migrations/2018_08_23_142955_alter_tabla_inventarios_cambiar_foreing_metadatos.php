<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTablaInventariosCambiarForeingMetadatos extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('inventario_metadatos', function (Blueprint $table) {
            $table->dropForeign('inventario_metadatos_metadatos_id_foreign');
        });
        Schema::table('inventario_metadatos', function (Blueprint $table) {
            $table->foreign('metadatos_id')->references('id')->on('articulos_metadatos');
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
