<?php

namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class PedidoCcClues extends BaseModel
{
    use SoftDeletes;
    protected $generarID = true;
    protected $guardarIDServidor = true;
    protected $guardarIDUsuario = true;
    protected $table = 'pedido_cc_clues';

     public function unidadMedica(){
        return $this->belongsTo('App\Models\UnidadMedica','clues');
    }
}
