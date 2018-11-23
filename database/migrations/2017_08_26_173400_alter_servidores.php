<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterServidores extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('servidores', function (Blueprint $table) {
            $table->timestamp('ultima_sincronizacion')->after('principal')->nullable();
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
            $table->dropColumn('ultima_sincronizacion');
        });
    }
}
