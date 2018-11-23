<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTablaStockAgregarPrograma extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('stock', function (Blueprint $table) {

            $table->integer('programa_id')->unsigned()->nullable()->after('clave_insumo_medico');
            $table->foreign('programa_id')->references('id')->on('programas');

         });

         Schema::table('stock_borrador', function (Blueprint $table) {

            $table->integer('programa_id')->unsigned()->nullable()->after('clave_insumo_medico');
            $table->foreign('programa_id')->references('id')->on('programas');

         });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('stock', function (Blueprint $table) {

            $table->dropForeign(['programa_id']);
            $table->dropColumn('programa_id');
            
         });
         Schema::table('stock_borrador', function (Blueprint $table) {

            $table->dropForeign(['programa_id']);
            $table->dropColumn('programa_id');
            
         });
    }
}
