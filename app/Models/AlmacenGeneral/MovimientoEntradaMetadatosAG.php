<?php 
namespace App\Models\AlmacenGeneral;


use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class MovimientoEntradaMetadatosAG extends BaseModel {

	use SoftDeletes;
    protected $generarID = true;
    protected $guardarIDServidor = true;
    protected $guardarIDUsuario = true;

    protected $primaryKey = 'id';
    
    protected $table = 'movimiento_entrada_metadatos_ag'; 

    public function Movimiento(){
		return $this->belongsTo('App\Models\AlmacenGeneral\Movimiento','movimiento_id','id');
    }

    public function Proveedor(){
      return $this->belongsTo('App\Models\Proveedor','proveedor_id','id');
  }

}
