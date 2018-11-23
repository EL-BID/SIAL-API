<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterCategoriasMetadatosArticulosMetadatos extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::rename('categorias_metadatos', 'articulos_metadatos');

        Schema::table('articulos_metadatos', function (Blueprint $table) {
            $table->dropForeign('categorias_metadatos_categoria_id_foreign');
        });
        Schema::table('articulos_metadatos', function (Blueprint $table) {
            $table->renameColumn('categoria_id', 'articulo_id');
        });
        Schema::table('articulos_metadatos', function (Blueprint $table) {
            $table->foreign('articulo_id')->references('id')->on('articulos');
        });

        Schema::table('articulos_metadatos', function (Blueprint $table) {
            $table->renameColumn('descripcion', 'valor');
        });
        Schema::table('articulos_metadatos', function (Blueprint $table) {
            $table->text('valor')->nullable()->change();
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
