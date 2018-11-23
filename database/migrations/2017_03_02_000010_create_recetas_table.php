<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRecetasTable extends Migration{
    /**
     * Run the migrations.
     * @table recetas
     *
     * @return void
     */
    public function up(){
 
        Schema::create('recetas', function(Blueprint $table) {
            $table->engine = 'InnoDB';
        
            $table->string('id', 255);
            $table->integer('incremento');
            $table->string('servidor_id', 4);
            $table->string('folio', 45);
            $table->string('tipo_receta', 45);
            $table->time('fecha_receta');
            $table->string('doctor', 255);
            $table->string('paciente', 255);
            $table->text('diagnostico');
            $table->text('imagen_receta');
            $table->string('usuario_id', 255);
            
            $table->primary('id');
        
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
       Schema::dropIfExists('recetas');
     }
}
