<?php
namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use \DB;


class Movimiento extends BaseModel{
    
    use SoftDeletes;
    protected $generarID = true;
    protected $guardarIDServidor = true;
    protected $guardarIDUsuario = true;

    protected $table = 'movimientos';
    protected $fillable = ['status','almacen_id','tipo_movimiento_id','fecha_movimiento','observaciones'];
    
    public function insumos(){
        return $this->hasMany('App\Models\MovimientoInsumos','movimiento_id')->with('stock.marca');
    }

    public function insumosDetalles(){
        return $this->hasMany('App\Models\MovimientoInsumos','movimiento_id')->with('detalles');
    }

    public function movimientoInsumos(){
        return $this->hasMany('App\Models\MovimientoInsumos','movimiento_id')->with('stockGrupo');
    }

    public function movimientoInsumosStock(){
        return $this->hasMany('App\Models\MovimientoInsumos','movimiento_id')->with('stock');
    }

    public function movimientoPedido(){
        return $this->hasOne('App\Models\MovimientoPedido','movimiento_id');
    }

    public function movimientoMetadato(){
        return $this->belongsTo('App\Models\MovimientoMetadato','id','movimiento_id')->with('turno','servicio','proveedor');
    }

    public function movimientoUsuario(){
        return $this->hasOne('App\Models\Usuario','id','usuario_id');
    }

    public function movimientoDetalle(){
        return $this->belongsTo('App\Models\MovimientoDetalle','id','movimiento_id');
    }

    public function almacen(){
        return $this->hasOne('App\Models\Almacen','id','almacen_id');
    }

    public function movimientoAjuste(){
        return $this->hasMany('App\Models\MovimientoAjuste','movimiento_id')->with('movimientoAjusteInsumo');
    }

     public function movimientoReceta(){
        return $this->belongsTo('App\Models\Receta','id','movimiento_id')->with('personalMedico');
    }

    public function programa(){
        return $this->hasOne('App\Models\Programa','id','programa_id');
    }
 
   
}