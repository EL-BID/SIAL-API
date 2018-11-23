<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTablaResguardosUpdate1 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('resguardos', function (Blueprint $table) {
            
            $table->string('clues_destino',12)->nullable()->after('almacen_id');
            $table->string('area_resguardante',255)->nullable()->after('fecha_resguardo');
            $table->string('nombre_resguardante',255)->nullable()->after('area_resguardante');
            $table->string('apellidos_resguardante',255)->nullable()->after('nombre_resguardante');
            
            $table->dropForeign('resguardos_personal_clues_id_foreign');
              
            $table->foreign('clues_destino')->references('clues')->on('unidades_medicas');
        });

        Schema::table('resguardos', function (Blueprint $table) {
             
            $table->dropColumn('personal_clues_id');
              
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
