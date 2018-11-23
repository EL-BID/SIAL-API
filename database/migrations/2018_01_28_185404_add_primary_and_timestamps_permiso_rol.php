<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPrimaryAndTimestampsPermisoRol extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('permiso_rol', function (Blueprint $table) {
            //
            $table->increments('id')->first();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('permiso_rol', function (Blueprint $table) {
            //
            $table->dropColumn('id');
            $table->dropColumn('created_at');
            $table->dropColumn('updated_at');            
            $table->dropColumn('deleted_at');
        });
    }
}
