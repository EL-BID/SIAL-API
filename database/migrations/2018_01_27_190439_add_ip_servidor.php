<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIpServidor extends Migration
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
            $table->string('ip',15)->nullable()->after('clues');
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
            $table->dropColumn('ip');
        });
    }
}
