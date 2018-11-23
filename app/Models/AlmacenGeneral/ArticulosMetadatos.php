<?php
namespace App\Models\AlmacenGeneral;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class ArticulosMetadatos extends BaseModel {

	use SoftDeletes;
    
    protected $generarID = false;
    protected $guardarIDServidor = false;
    protected $guardarIDUsuario = true;
    public $incrementing = true;

    protected $primaryKey = 'id';
    
    protected $table = 'articulos_metadatos'; 

	public function Articulos(){
		return $this->belongsTo('App\Models\AlmacenGeneral\Articulos','articulo_id','id');
    }

}
