<?php
namespace App\Models\AlmacenGeneral;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class Articulos extends BaseModel {

	use SoftDeletes;
    
    protected $generarID = false;
    protected $guardarIDServidor = false;
    protected $guardarIDUsuario = true;
    public $incrementing = true;

    protected $primaryKey = 'id';
    
    protected $table = 'articulos'; 

    public function Padre(){
		return $this->belongsTo('App\Models\AlmacenGeneral\Articulos','articulo_id','id');
    }

    public function Hijos(){
		return $this->hasMany('App\Models\AlmacenGeneral\Articulos','articulo_id','id');
    }

    public function Categoria(){
		return $this->belongsTo('App\Models\AlmacenGeneral\Categorias','categoria_id','id');
    }

    public function ArticulosMetadatos(){
		return $this->hasMany('App\Models\AlmacenGeneral\ArticulosMetadatos','articulo_id','id');
    }

    public function Inventarios(){
      return $this->hasMany('App\Models\AlmacenGeneral\Inventario','articulo_id','id')->with('Articulo','Programa','MovimientoArticulo','InventarioMetadatoUnico');
    }  

}
