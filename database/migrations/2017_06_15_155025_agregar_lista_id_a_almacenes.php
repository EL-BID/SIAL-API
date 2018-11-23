<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AgregarListaIdAAlmacenes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('almacenes', function (Blueprint $table) {
            $table->integer('lista_insumo_id')->unsigned()->nullable()->after('subrogado');
            $table->foreign('lista_insumo_id')->references('id')->on('listas_insumos');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('almacenes', function (Blueprint $table) {
            $table->dropForeing(['lista_insumo_id']);
            $table->dropColumn('lista_insumo_id');
        });
    }
}
