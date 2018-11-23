<?php

namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class Proveedor extends BaseModel
{
    use SoftDeletes;
    protected $generarID = true;
    protected $guardarIDServidor = true;
    protected $guardarIDUsuario = true;
    protected $fillable = ["id","nombre","nombre_corto","rfc","direccion","ciudad","contacto","cargo_contacto","telefono","celular","email","activo"];
    protected $table = 'proveedores'; 
    
    public function contratos(){
        return $this->hasMany('App\Models\Contrato','proveedor_id');
    }

    public function contratoActivo(){
        return $this->hasOne('App\Models\Contrato','proveedor_id')->orderBy('fecha_fin','DESC')->where('contratos.activo','=',1);
    }

}

