<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterNullableTurnoServicio extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('movimiento_metadatos', function (Blueprint $table)
        {
                $table->integer('turno_id')->nullable()->unsigned()->change();
                $table->integer('servicio_id')->nullable()->unsigned()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('movimiento_metadatos', function (Blueprint $table)
        {
                $table->integer('turno_id')->unsigned()->change();
                $table->integer('servicio_id')->unsigned()->change();
        });
    }
}
