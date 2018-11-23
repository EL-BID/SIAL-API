<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterResguardosAgregarCampoStatus extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('resguardos', function (Blueprint $table) {
            
            $table->string('status',55)->nullable()->after('observaciones')->comment('ACTIVO - DEVUELTO - DEVUELTO_PARCIALMENTE');
            
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
