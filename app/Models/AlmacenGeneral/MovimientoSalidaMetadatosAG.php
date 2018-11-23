<?php 
namespace App\Models\AlmacenGeneral;


use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class MovimientoSalidaMetadatosAG extends BaseModel {

	use SoftDeletes;
    protected $generarID = true;
    protected $guardarIDServidor = true;
    protected $guardarIDUsuario = true;

    protected $primaryKey = 'id';
    
    protected $table = 'movimiento_salida_metadatos_ag'; 

	public function UnidadMedica(){
		return $this->belongsTo('App\Models\UnidadMedica','clues_destino','clues');
    }

    public function Movimiento(){
		return $this->belongsTo('App\Models\AlmacenGeneral\Movimiento','movimiento_id','id');
    }

}
