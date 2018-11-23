<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTablaRecetas extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
       
        Schema::table('recetas', function (Blueprint $table)
        {
                //$table->dropForeign('receta_detalles_receta_id_foreign') ;

            $table->string('movimiento_id')->after('servidor_id');
            $table->string('folio_receta')->after('folio');
            $table->dropColumn('tipo_receta');
            $table->integer('tipo_receta_id')->unsigned()->after('folio_receta');
            $table->date('fecha_receta')->change();
            $table->date('fecha_surtido')->after('fecha_receta');
            $table->dropColumn('imagen_receta');

            $table->foreign('tipo_receta_id')->references('id')->on('tipos_recetas');
            $table->foreign('movimiento_id')->references('id')->on('movimientos');


        });
            
            
           
 
        Schema::table('receta_detalles', function(Blueprint $table) {
       
           
          $table->integer('cantidad_recetada')->after('clave_insumo_medico');
          
         
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
