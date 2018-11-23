<?php
namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class HistorialMedicamento extends BaseModel{
    
    use SoftDeletes;
    
    protected $generarID = false;
    protected $guardarIDServidor = false;
    protected $guardarIDUsuario = true;
    //public $incrementing = false;
    
    protected $table = 'historial_medicamentos';  
    //protected $primaryKey = 'insumo_medico_clave';
    protected $fillable = ["id","insumo_medico_clave","presentacion_id","es_controlado","es_surfactante","concentracion","contenido","cantidad_x_envase","unidad_medida_id","indicaciones","via_administracion_id","dosis"];
    protected $casts = ["insumo_medico_clave" => "string","forma_farmaceutica_id" => "integer" ,"presentacion_id" => "integer" ,"es_controlado" => "boolean" ,"es_surfactante" => "boolean" ,"concentracion" => "string" ,"contenido" => "string" ,"cantidad_x_envase" => "float" ,"unidad_medida_id" => "integer" ,"via_administracion_id" => "integer"];        
}