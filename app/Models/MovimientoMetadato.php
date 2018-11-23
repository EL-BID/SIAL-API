<?php
namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class MovimientoMetadato extends BaseModel{
    
    use SoftDeletes;
    protected $generarID = true;
    protected $guardarIDServidor = true;
    protected $guardarIDUsuario = true;

    protected $table = 'movimiento_metadatos';

    public function turno(){
        return $this->belongsTo('App\Models\Turno','turno_id');
    }

    public function servicio(){
        return $this->belongsTo('App\Models\Servicio','servicio_id');
    }

    public function proveedor(){
        return $this->belongsTo('App\Models\Proveedor','proveedor_id');
    }
    

  
 

}