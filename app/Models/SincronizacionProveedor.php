<?php
namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class SincronizacionProveedor extends BaseModel{
    
    use SoftDeletes;
    protected $generarID = true;
    protected $guardarIDServidor = true;
    protected $guardarIDUsuario = true;

    protected $table = 'sincronizaciones_proveedores';

    public function pedido(){
        return $this->belongsTo('App\Models\Pedido','pedido_id','id');
    }

     public function almacen(){
        return $this->belongsTo('App\Models\Almacen','almacen_id','id');
    }

    public function proveedor(){
        return $this->belongsTo('App\Models\Proveedor','proveedor_id','id');
    }

     public function unidadMedica(){
        return $this->belongsTo('App\Models\UnidadMedica','clues','clues');
      }

 
}