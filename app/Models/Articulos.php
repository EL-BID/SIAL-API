<?php
namespace App\Models;

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
		return $this->belongsTo('App\Models\Articulos','articulo_id','id');
    }

    public function Hijos(){
		return $this->hasMany('App\Models\Articulos','articulo_id','id');
    }

    public function Categoria(){
		return $this->belongsTo('App\Models\Categorias','categoria_id','id');
    }

    public function ArticulosMetadatos(){
		return $this->hasMany('App\Models\ArticulosMetadatos','articulo_id','id');
    }

    public function Inventarios(){
    return $this->hasMany('App\Models\AlmacenGeneral\Inventario','articulo_id','id')->with('Articulo','MovimientoArticulo','InventarioMetadatoUnico');
    }    

}
