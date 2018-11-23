<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterUnidadesMedicasTipos extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('unidades_medicas', function (Blueprint $table)
        {
               
                $table->string('tipo', 4)->after('jurisdiccion_id')->nullable()->comment('HO = Hospital\nHBC = Hospital Basico Comunitario\nCS = Centro de Salud\nOA = Oficinas administrativas');  ;
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('unidades_medicas', function (Blueprint $table)
        {               
                $table->dropColumn('tipo');        
        });
    }
}
