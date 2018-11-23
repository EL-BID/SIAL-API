<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTablaPersonalCluesCamposControlados extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('personal_clues', function (Blueprint $table)
        {
            $table->tinyInteger('surte_controlados')->after('nombre');
            $table->string('licencia_controlados')->after('surte_controlados');             
        });
        
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('personal_clues', function (Blueprint $table)
        {
            $table->dropColumn('surte_controlados');
            $table->dropColumn('licencia_controlados');
         });
        
    }
}
