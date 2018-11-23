<?php
namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use DB;

class HistorialInsumo extends BaseModel{
    
    use SoftDeletes;
    
    protected $generarID = false;
    protected $guardarIDServidor = false;
    protected $guardarIDUsuario = true;    
    public $incrementing = true;
    protected $table = 'historial_insumos_medicos';  
   // protected $primaryKey = 'clave';
    public $fillable = ["id","clave","atencion_medica","salud_publica","tipo","generico_id","es_causes","es_unidosis","tiene_fecha_caducidad","descontinuado","descripcion"];
    protected $casts = ["clave"=>"string","tipo"=>"string", "descripcion"=>"string","es_causes"=>"boolean", "es_unidosis"=>"boolean", "descontinuado"=>"boolean",  "salud_publica"=>"boolean",  "atencion_medica"=>"boolean", "tiene_fecha_caducidad"=>"boolean", "generico_id"=>"integer"];

    //Relacion con el Modelo Medicamento
    public function medicamento(){
        return $this->hasOne('App\Models\HistorialMedicamento','historial_id','id');
    }

    //Relacion con el Modelo Material Curación
    public function materialCuracion(){
        return $this->hasOne('App\Models\HistorialMaterialCuracion','historial_id','id');
    }
}