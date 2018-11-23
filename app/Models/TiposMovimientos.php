<?php
namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class TiposMovimientos extends BaseModel{
    
    use SoftDeletes;
    protected $generarID = true;
    protected $guardarIDServidor = true;
    protected $guardarIDUsuario = true;

    //protected $table = 'tipos_movimientos';
    
    public function roles(){
		return $this->belongsToMany('App\Models\Rol', 'permiso_rol', 'permiso_id', 'rol_id');
	}

   

}