<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableAvancesPrioridadEstatus extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('avances', function (Blueprint $table) {
            $table->integer('prioridad')->default(1)->after('comentario');
            $table->boolean('estatus')->default(0)->after('prioridad');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        chema::table('avances', function (Blueprint $table) {
            $table->dropColumn('prioridad');
            $table->dropColumn('estatus');
        });
    }
}
