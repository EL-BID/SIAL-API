<?php 
namespace App\Models\AlmacenGeneral;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class Categorias extends BaseModel {

	use SoftDeletes;
    
    protected $generarID = false;
    protected $guardarIDServidor = false;
    protected $guardarIDUsuario = true;
    public $incrementing = true;

    protected $primaryKey = 'id';
    
    protected $table = 'categorias'; 

    public function Padre(){
      return $this->belongsTo('App\Models\AlmacenGeneral\Categorias','categoria_id','id');
    }

    public function Hijos(){
		return $this->hasmany('App\Models\AlmacenGeneral\Categorias','categoria_id','id');
    }
}
