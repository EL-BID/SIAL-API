<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTablaInicializacionInventarioNullableFechas extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('inicializacion_inventario', function (Blueprint $table) {
            $table->date('fecha_fin')->nullable()->change();
        });

        Schema::table('inicializacion_inventario_detalles', function (Blueprint $table) {
            $table->boolean('exclusivo')->after('marca_id');
        });

        Schema::table('stock', function (Blueprint $table) {
            $table->boolean('exclusivo')->after('marca_id');
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('inicializacion_inventario', function (Blueprint $table) {
            $table->date('fecha_fin')->change();
        });

        Schema::table('inicializacion_inventario_detalles', function (Blueprint $table) {
            $table->dropColumn('exclusivo');
        });

        Schema::table('stock', function (Blueprint $table) {
            $table->dropColumn('exclusivo');
        });

    }
}
