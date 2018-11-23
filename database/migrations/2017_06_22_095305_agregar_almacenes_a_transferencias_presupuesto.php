<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AgregarAlmacenesATransferenciasPresupuesto extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transferencias_presupuesto', function (Blueprint $table){
            $table->string('almacen_origen')->after('clues_origen')->nullable();
            $table->string('almacen_destino')->after('clues_destino')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
         Schema::table('transferencias_presupuesto', function (Blueprint $table){               
             $table->dropColumn('almacen_origen');
             $table->dropColumn('almacen_destino');
        });
    }
}
