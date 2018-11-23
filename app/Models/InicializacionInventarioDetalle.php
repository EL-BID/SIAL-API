<?php
namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use \DB;


class InicializacionInventarioDetalle extends BaseModel{
    
    use SoftDeletes;
    protected $generarID = true;
    protected $guardarIDServidor = true;
    protected $guardarIDUsuario = true;

    protected $table = 'inicializacion_inventario_detalles';
    //protected $fillable = ['status','almacen_id','tipo_movimiento_id','fecha_movimiento','observaciones'];

    public function inicializacion_inventario_detalles(){
        return $this->hasMany('App\Models\MovimientoInsumos','movimiento_id')->with('stock.marca');
    } 
    
    public function insumos(){
        return $this->hasMany('App\Models\MovimientoInsumos','movimiento_id')->with('stock.marca');
    } 

    public function movimientoInsumos(){
        return $this->hasMany('App\Models\MovimientoInsumos','movimiento_id')->with('stockGrupo');
    }

    public function movimientoInsumosStock(){
        return $this->hasMany('App\Models\MovimientoInsumos','movimiento_id')->with('stock');
    }

    
    public function movimientoMetadato(){
        return $this->belongsTo('App\Models\MovimientoMetadato','id','movimiento_id')->with('turno','servicio');
    }

    public function movimientoUsuario(){
        return $this->hasOne('App\Models\Usuario','id','usuario_id');
    }

     

    public function almacen(){
        return $this->hasOne('App\Models\Almacen','id','almacen_id');
    }

     

      
 
   
}