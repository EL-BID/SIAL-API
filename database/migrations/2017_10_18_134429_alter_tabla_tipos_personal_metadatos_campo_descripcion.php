<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTablaTiposPersonalMetadatosCampoDescripcion extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tipos_personal_metadatos', function (Blueprint $table)
        {    
             $table->string('descripcion',255)->after('campo');
             $table->string('requerido',255)->after('longitud');         
             
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tipos_personal_metadatos', function (Blueprint $table)
        {   
            $table->dropColumn('requerido');          
            $table->dropColumn('descripcion');
        });
    }
}
