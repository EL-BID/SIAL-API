<?php
namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class AlmacenTiposMovimientos extends BaseModel{
    
    use SoftDeletes;
    protected $generarID = true;
    protected $guardarIDServidor = true;
    protected $guardarIDUsuario = true;

    protected $table = 'almacen_tipos_movimientos';

    public function almacenes()
    {
		  return $this->belongsTo('App\Models\Almacenes','almacen_id');
    }
    public function tipoMovimiento()
    {
		  return $this->hasOne('App\Models\TiposMovimientos','id','tipo_movimiento_id');
    }
}