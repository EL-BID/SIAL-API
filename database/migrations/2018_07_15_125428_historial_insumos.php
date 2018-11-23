<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class HistorialInsumos extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('historial_insumos_medicos', function(Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');        
            $table->string('clave', 25);
            $table->string('tipo', 2)->nullable()->default('ME')->comment('ME = Medicamentos\nMC = Material de curación\nAD = Auxiliares de Diagnostico');
            $table->integer('generico_id')->unsigned();
            $table->boolean('atencion_medica')->default(false)->nullable();
            $table->boolean('salud_publica')->default(false)->nullable();
            $table->boolean('es_causes')->nullable()->default(null);
            $table->boolean('es_unidosis')->nullable()->default(null);
            $table->boolean('tiene_fecha_caducidad')->default(0);
            $table->text('descripcion')->nullable();
            $table->boolean('descontinuado')->default(false)->nullable();
           
           // $table->foreign('generico_id')->references('id')->on('genericos');
            $table->string('usuario_id',255);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('historial_medicamentos', function(Blueprint $table) {
		    $table->engine = 'InnoDB';
		
            $table->increments('id');
            $table->integer('historial_id')->unsigned();
            $table->string('insumo_medico_clave', 25);
            $table->integer('forma_farmaceutica_id')->unsigned()->nullable();
		    $table->integer('presentacion_id')->unsigned();
		    $table->boolean('es_controlado')->nullable()->default(null);
		    $table->boolean('es_surfactante')->nullable()->default(null);
		    
			$table->string('concentracion', 150)->nullable();
			$table->string('contenido', 100)->nullable();

		    $table->decimal('cantidad_x_envase', 15, 2)->nullable()->default(null);
		    $table->integer('unidad_medida_id')->unsigned()->comment('Del contenido del envase')->nullable();
		    $table->text('indicaciones')->nullable();
		    $table->integer('via_administracion_id')->unsigned()->nullable();
		    $table->text('dosis')->nullable();
            
            $table->string('usuario_id',255);

            /*
		    $table->foreign('insumo_medico_clave')->references('clave')->on('insumos_medicos')->onUpdate('cascade');
		    $table->foreign('presentacion_id')->references('id')->on('presentaciones_medicamentos');
		    $table->foreign('unidad_medida_id')->references('id')->on('unidades_medida');
		    $table->foreign('via_administracion_id')->references('id')->on('vias_administracion');
        */          
            
            $table->foreign('historial_id')->references('id')->on('historial_insumos_medicos');

		    $table->timestamps();
			$table->softDeletes();
        });
        
        Schema::create('historial_material_curacion', function(Blueprint $table) {
            $table->engine = 'InnoDB';
        
            $table->increments('id');
            $table->integer('historial_id')->unsigned();
            $table->string('insumo_medico_clave', 25);
            $table->text('nombre_generico_especifico')->nullable();
            $table->decimal('cantidad_x_envase', 15, 2);
            $table->integer('unidad_medida_id')->unsigened()->comment('Del contenido del envase');
            $table->text('funcion')->nullable();
                      
            //$table->foreign('insumo_medico_clave')->references('clave')->on('insumos_medicos')->onUpdate('cascade');
            $table->string('usuario_id',255);

            $table->foreign('historial_id')->references('id')->on('historial_insumos_medicos');

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
        //
        Schema::dropIfExists('historial_medicamentos');
        Schema::dropIfExists('historial_material_curacion');
        Schema::dropIfExists('historial_insumos_medicos');
    }
}
