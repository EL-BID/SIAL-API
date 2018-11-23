<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterMovimientoInsumosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('movimiento_insumos', function (Blueprint $table)
        {
            if(!Schema::hasColumn('stock_id', 'clave_insumo_medico')) {
                $table->string('stock_id', 255)->nullable()->change();
                $table->string('clave_insumo_medico', 255)->after('stock_id');

                $table->foreign('clave_insumo_medico')->references('clave')->on('insumos_medicos')->onUpdate('cascade');
            }
                
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('movimiento_insumos', function (Blueprint $table)
        {
                $table->string('stock_id', 255)->change();
                $table->dropForeing(['clave_insumo_medico']);
                $table->dropColumn('clave_insumo_medico');  
        });
    }
}
