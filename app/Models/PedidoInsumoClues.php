<?php

namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class PedidoInsumoClues extends BaseModel
{
    use SoftDeletes;
    protected $generarID = true;
    protected $guardarIDServidor = true;
    protected $guardarIDUsuario = true;
    protected $fillable = [ "pedido_insumo_id", "clues", "cantidad", "created_at","updated_at"];
    protected $table = 'pedidos_insumos_clues';

    

    public function pedidoInsumo(){
        return $this->belongsTo('App\Models\PedidoInsumo','pedido_insumo_id');
    }

    public function scopeConNombre($query){
        return $query->select('pedidos_insumos_clues.*','unidades_medicas.nombre') 
                ->leftjoin('unidades_medicas','unidades_medicas.clues','=','pedidos_insumos_clues.clues');
    }

}
