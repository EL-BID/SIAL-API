<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePersonalCluesPuestoTable extends Migration{
    /**
     * Run the migrations.
     * @table personal_clues
     *
     * @return void
     */
    public function up(){
        Schema::create('personal_clues_puesto', function(Blueprint $table) {
		    $table->engine = 'InnoDB';
		
		    $table->string('id', 255);
		    $table->integer('incremento');
		    $table->string('servidor_id', 4);

		    $table->string('personal_id', 255);
		    $table->string('puesto_id', 255);
		    $table->date('fecha_inicio')->nullable();
		    $table->date('fecha_fin')->nullable();
		    
		    $table->primary('id');
		/*
		    $table->index('puesto_id','fk_firmas_documentos_puestos1_idx');
		    $table->index('usuario_id','fk_firmas_clues_usuarios1_idx');
		    $table->index('servicio_id','fk_personal_clues_puesto_servicios1_idx');
		*/
		    $table->foreign('puesto_id')->references('id')->on('puestos');
            $table->foreign('personal_id')->references('id')->on('personal_clues');
		    //$table->foreign('usuario_id')->references('id')->on('usuarios');
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
       Schema::dropIfExists('personal_clues_puesto');
     }
}
