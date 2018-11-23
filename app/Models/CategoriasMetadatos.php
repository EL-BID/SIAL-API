<?php
namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class CategoriasMetadatos extends BaseModel {

	use SoftDeletes;
    
    protected $generarID = false;
    protected $guardarIDServidor = false;
    protected $guardarIDUsuario = true;
    public $incrementing = true;

    protected $primaryKey = 'id';
    
    protected $table = 'categorias_metadatos'; 

	public function Categorias(){
		return $this->belongsTo('App\Models\Categorias','categoria_id','id');
    }

}
