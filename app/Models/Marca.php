<?php
namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class Marca extends BaseModel{
    
    use SoftDeletes;
    protected $generarID = false;
    protected $guardarIDServidor = false;
    protected $guardarIDUsuario = false;

    protected $table = 'marcas';

    public function insumo(){
        return $this->belongsTo('App\Models\Insumo','clave_insumo_medico','clave');        
    }
    public function almacen(){
        return $this->belongsTo('App\Models\Almacen','almacen_id','id'); 
    }
    public function marca(){
        return $this->belongsTo('App\Models\Marca','marca_id','id'); 
    }
    
   /* public function roles(){
      return $this->belongsToMany('App\Models\Rol', 'permiso_rol', 'permiso_id', 'rol_id');
    }*/
 

}