<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRepositorioProveedor extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('repositorio', function (Blueprint $table) {
            $table->string('id', 255);
            $table->integer('incremento');
            $table->string('servidor_id', 4);
            $table->string('pedido_id', 255);
            $table->decimal('peso', 15,2);
            $table->string('extension', 100);
            $table->string('usuario_id', 255);
            $table->string('usuario_deleted_id', 255);
            $table->string('nombre_archivo', 255);
            $table->string('ubicacion', 255);
            
            $table->foreign('pedido_id')->references('id')->on('pedidos');
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
    public function down()
    {
        Schema::dropIfExists('repositorio');
    }
}
