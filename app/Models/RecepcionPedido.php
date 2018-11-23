<?php

namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class RecepcionPedido extends BaseModel
{
     use SoftDeletes;
    protected $generarID = true;
    protected $guardarIDServidor = true;
    protected $guardarIDUsuario = true;
    protected $table = 'movimientos';

    protected $fillable = ["status","almacen_id","tipo_movimiento_id", "fecha_movimiento", "observaciones", "cancelado", "observaciones_cancelacion"];

    public function movimientoInsumo(){
        return $this->belongsTo('App\Models\MovimientoInsumos', 'id', 'movimiento_id');
    }

}
