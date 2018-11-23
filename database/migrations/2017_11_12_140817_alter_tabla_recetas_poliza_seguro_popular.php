<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTablaRecetasPolizaSeguroPopular extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('recetas', function (Blueprint $table) {

            $table->string('poliza_seguro_popular',55)->nullable()->after('paciente');
 
         });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('recetas', function (Blueprint $table) {

             $table->dropColumn('poliza_seguro_popular');
            
         });
    }
}
