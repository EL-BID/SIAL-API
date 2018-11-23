<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateInsumosMedicosTable extends Migration{
    /**
     * Run the migrations.
     * @table insumos_medicos
     *
     * @return void
     */
    public function up(){
      Schema::create('insumos_medicos', function(Blueprint $table) {
          $table->engine = 'InnoDB';
      
          $table->string('clave', 25);
          $table->string('tipo', 2)->nullable()->default('ME')->comment('ME = Medicamentos\nMC = Material de curación\nAD = Auxiliares de Diagnostico');
          $table->integer('generico_id')->unsigned();
          $table->boolean('es_causes')->nullable()->default(null);
          $table->boolean('es_unidosis')->nullable()->default(null);
          $table->boolean('tiene_fecha_caducidad')->default(0);
          $table->text('descripcion')->nullable();
          
          $table->primary('clave');
          //$table->index('generico_id','fk_auxiliares_diagnostico_genericos_idx');
          $table->foreign('generico_id')->references('id')->on('genericos');
      
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
       Schema::dropIfExists('insumos_medicos');
     }
}
