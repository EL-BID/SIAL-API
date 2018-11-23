<?php 
namespace App\Models\AlmacenGeneral;


use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventarioArticulo extends BaseModel {

	use SoftDeletes;
    protected $generarID = true;
    protected $guardarIDServidor = true;
    protected $guardarIDUsuario = true;
    
    protected $table = 'inventario'; 


    public function Articulo(){
        return $this->belongsTo('App\Models\Articulos','articulo_id','id');
    }

    public function Almacen(){
        return $this->belongsTo('App\Models\Almacen','almacen_id','id');
    }

    public function ResguardosArticulos(){
        return $this->hasMany('App\Models\AlmacenGeneral\ResguardosArticulos','inventario_id');
    }

    public function InventarioMetadato(){
        return $this->hasmany('App\Models\AlmacenGeneral\InventarioArticuloMetadatos','inventario_id','id')
        ->leftjoin('articulos_metadatos','articulos_metadatos.id','=','inventario_metadatos.metadatos_id');
    }

    public function InventarioArticuloMetadato(){
        return $this->hasmany('App\Models\AlmacenGeneral\InventarioArticuloMetadatos','inventario_id','id')
        ->leftjoin('articulos_metadatos','articulos_metadatos.id','=','inventario_metadatos.metadatos_id');
    }

    public function InventarioMetadatoUnico(){
        return $this->hasmany('App\Models\AlmacenGeneral\InventarioMetadato','inventario_id','id');
    }

    public function Programa(){
        return $this->belongsTo('App\Models\Programa','programa_id','id');
    }
}
