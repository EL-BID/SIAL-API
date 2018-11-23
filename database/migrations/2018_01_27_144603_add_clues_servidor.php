<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCluesServidor extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('servidores', function (Blueprint $table) {
            //
            $table->string('clues',45)->nullable()->after('secret_key');
            $table->foreign('clues')->references('clues')->on('unidades_medicas');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('servidores', function (Blueprint $table) {
            //
            $table->dropForeign(['clues']);
            $table->dropColumn('clues');
        });
    }
}
