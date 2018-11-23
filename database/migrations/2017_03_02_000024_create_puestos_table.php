<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePuestosTable extends Migration{
    /**
     * Run the migrations.
     * @table puestos
     *
     * @return void
     */
    public function up(){
        Schema::create('puestos', function(Blueprint $table) {
            $table->engine = 'InnoDB';
        
            $table->string('id', 255);
            $table->integer('incremento');
            $table->string('servidor_id', 4);
            //$table->string('servicio_id', 255);
            $table->string('clave', 45);
            $table->string('nombre', 255);
            
            $table->string('usuario_id', 255);
            
            $table->primary('id');
            $table->unique('clave','clave_UNIQUE');
            //$table->index('servicio_id','fk_puestos_servicios1_idx');
        
            //$table->foreign('servicio_id')->references('id')->on('clues_servicios');
        
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
       Schema::dropIfExists('puestos');
     }
}
