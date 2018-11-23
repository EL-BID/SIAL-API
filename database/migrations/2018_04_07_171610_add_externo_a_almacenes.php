<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddExternoAAlmacenes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('almacenes', function (Blueprint $table) {
            $table->boolean('externo')->default(0)->after('subrogado');
            $table->string('clues_perteneciente', 45)->after('clues');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('almacenes', function (Blueprint $table) {
            $table->dropColumn('externo');
            $table->dropColumn('clues_perteneciente');
        });
    }
}
