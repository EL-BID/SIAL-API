<?php

namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class Acta extends BaseModel
{
    use SoftDeletes;
    protected $generarID = true;
    protected $guardarIDServidor = true;
    protected $guardarIDUsuario = true;

    protected $table = 'actas';  
    
    protected $fillable = [
            "nombre",
            "folio",
            "clues",
            "numero",
            "numero_oficio",
            "numero_oficio_pedido",
            "ciudad",
            "fecha",
            "fecha_solicitud",
            "fecha_pedido",
            "hora_inicio",
            "hora_termino",
            "lugar_reunion",
            "lugar_entrega",
            "director_unidad",
            "administrador_unidad",
            "encargado_almacen",
            "proveedor_id",
            "fecha_cancelacion",
            "motivo_cancelacion",
            "created_at","updated_at"];
    
    public function director(){
        return $this->hasOne('App\Models\PersonalClues','id','director_unidad');
    }
    public function administrador(){
        return $this->hasOne('App\Models\PersonalClues','id','administrador_unidad');
    }

    public function personaEncargadaAlmacen(){
        return $this->hasOne('App\Models\PersonalClues','id','encargado_almacen');
    }
    public function proveedor(){
        return $this->belongsTo('App\Models\Proveedor','proveedor_id','id');
    }
    public function unidadMedica(){
        return $this->belongsTo('App\Models\UnidadMedica','clues','clues');
    }
    public function pedidos(){
        return $this->hasMany('App\Models\Pedido','acta_id', 'id');
    }
}
