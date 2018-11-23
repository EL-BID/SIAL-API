<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateInsumoMedicoServicioTable extends Migration{
    /**
     * Run the migrations.
     * @table insumo_medico_servicio
     *
     * @return void
     */
    public function up(){
       Schema::create('insumo_medico_servicio', function(Blueprint $table) {
          $table->engine = 'InnoDB';
      
          $table->string('insumo_medico_clave', 25);
          $table->integer('servicio_id')->unsigned();
          
          $table->primary('insumo_medico_clave', ' servicio_id');
      
          //$table->index('servicio_id','fk_insumos_medicos_has_servicios_servicios1_idx');
          //$table->index('insumo_medico_clave','fk_insumos_medicos_has_servicios_insumos_medicos1_idx');
      
          $table->foreign('insumo_medico_clave')->references('clave')->on('insumos_medicos')->onUpdate('cascade');
          $table->foreign('servicio_id')->references('id')->on('servicios');
      
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
       Schema::dropIfExists('insumo_medico_servicio');
     }
}
