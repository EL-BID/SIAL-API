<?php
namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class Proveedores extends BaseModel{
    
    use SoftDeletes;
    protected $generarID = false;
    protected $guardarIDServidor = false;
    protected $guardarIDUsuario = true;

    public function contactos(){
        return $this->hasMany('App\Models\Contactos','proveedor_id');
    }
    public function comunicacionContacto(){
        return $this->hasMany('App\Models\ComunicacionContacto','proveedor_id');
    }
   

}