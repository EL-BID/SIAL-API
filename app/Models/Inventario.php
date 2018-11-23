<?php
namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use \DB;


class Inventario extends BaseModel{
    
    use SoftDeletes;
    protected $generarID = true;
    protected $guardarIDServidor = true;
    protected $guardarIDUsuario = true;

    protected $table = 'inventarios';
    protected $fillable = ['fecha_inicio_captura','fecha_conclusion_captura','descripcion','observaciones','status','almacen_id','clues','total_claves','total_monto_causes','total_monto_no_causes','total_monto_material_curacion'];
    
    public function insumos(){
        return $this->hasMany('App\Models\InventarioDetalle','inventario_id');
    }

    public function insumosDetalles(){
        return $this->hasMany('App\Models\InventarioDetalle','inventario_id')->with('detalles');
    }

    public function almacen(){
        return $this->hasOne('App\Models\Almacen','id','almacen_id');
    }

    public function unidadMedica(){
        return $this->hasOne('App\Models\UnidadMedica','clues','clues');
    }
   
}