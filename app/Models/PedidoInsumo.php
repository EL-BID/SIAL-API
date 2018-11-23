<?php

namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class PedidoInsumo extends BaseModel
{
    use SoftDeletes;
    protected $generarID = true;
    protected $guardarIDServidor = true;
    protected $guardarIDUsuario = true;
    protected $fillable = [ "pedido_id", "insumo_medico_clave", "cantidad", "cantidad_solicitada", "cantidad_recibida","precio_unitario","tipo_insumo_id","monto","monto_solicitado","monto_recibido", "created_at","updated_at"];
    protected $table = 'pedidos_insumos';

    public function insumoDetalle(){
        return $this->hasOne('App\Models\Insumo','clave','insumo_medico_clave')->withTrashed();
    }

    public function insumosConDescripcion(){
        return $this->hasOne('App\Models\Insumo','clave','insumo_medico_clave')->conDescripciones()->withTrashed();        
    }

    public function conDatosInsumo(){
        return $this->hasOne('App\Models\Insumo','clave','insumo_medico_clave')->datosUnidosis()->withTrashed();        
    }

    public function infoInsumo(){
        return $this->hasOne('App\Models\Insumo','clave','insumo_medico_clave');        
    }

    public function tipoInsumo(){
        return $this->belongsTo('App\Models\TipoInsumo','tipo_insumo_id');
    }

    public function listaClues(){
        return $this->hasMany('App\Models\PedidoInsumoClues','pedido_insumo_id','id')->conNombre();
    }

}
