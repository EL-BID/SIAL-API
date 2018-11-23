<?php

namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class PedidoMetadatoCC extends BaseModel
{
    use SoftDeletes;
    protected $generarID = true;
    protected $guardarIDServidor = true;
    protected $guardarIDUsuario = true;
    protected $table = 'pedido_metadatos_cc';

    public function programa(){
        return $this->belongsTo('App\Models\Programa','programa_id');
    }

     

}
