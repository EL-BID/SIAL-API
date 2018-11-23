<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRecetaDetallesTable extends Migration{
    /**
     * Run the migrations.
     * @table receta_detalles
     *
     * @return void
     */
    public function up(){
       Schema::create('receta_detalles', function(Blueprint $table) {
          $table->engine = 'InnoDB';
      
          $table->string('id', 255);
          $table->integer('incremento');
          $table->string('servidor_id', 4);
          $table->string('receta_id', 255);
          $table->string('clave_insumo_medico', 255);
          $table->integer('cantidad');
          $table->decimal('dosis', 15, 2);
          $table->decimal('frecuencia', 15, 2);
          $table->string('duracion', 45);
          $table->string('usuario_id', 255);
          
          $table->primary('id');
      
          $table->foreign('receta_id')->references('id')->on('recetas');
      
          $table->timestamps();
          $table->softDeletes();
      });

     

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
     public function down(){
       Schema::dropIfExists('receta_detalles');
     }
}
