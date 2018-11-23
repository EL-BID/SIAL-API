<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTablaTiposPersonalIncrement extends Migration
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
            $table->dropForeign('personal_clues_tipo_personal_id_foreign');
            $table->dropColumn('tipo_personal_id');
        });

        Schema::table('tipos_personal_metadatos', function (Blueprint $table)
        {             
            $table->dropForeign('tipos_personal_metadatos_tipo_personal_id_foreign');
            $table->dropColumn('tipo_personal_id');
        });

        

        Schema::table('tipos_personal', function (Blueprint $table)
        {             
            $table->increments('id')->unsigned()->change();
        });
        Schema::table('tipos_personal_metadatos', function (Blueprint $table)
        {             
            $table->increments('id')->unsigned()->change();
        });


        Schema::table('personal_clues', function (Blueprint $table)
        {
            $table->integer('tipo_personal_id')->unsigned()->nullable()->after('clues');
            $table->foreign('tipo_personal_id')->references('id')->on('tipos_personal');
        });
        Schema::table('tipos_personal_metadatos', function (Blueprint $table)
        {
            $table->integer('tipo_personal_id')->unsigned()->nullable()->after('id');
            $table->foreign('tipo_personal_id')->references('id')->on('tipos_personal');
        });
        


        



    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        
    }
}
