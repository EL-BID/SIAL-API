<?php
namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class HistorialMaterialCuracion extends BaseModel{
    
    use SoftDeletes;
    
    protected $generarID = false;
    protected $guardarIDServidor = false;
    protected $guardarIDUsuario = true;
    public $incrementing = true;
    
    protected $table = 'historial_material_curacion';  
    //protected $primaryKey = 'insumo_medico_clave';
    protected $fillable = ["id","insumo_medico_clave","nombre_generico_especifico","funcion","cantidad_x_envase","unidad_medida_id"];
    protected $casts = ["insumo_medico_clave" => "string","nombre_generico_especifico" => "string","cantidad_x_envase" => "float" ,"unidad_medida_id" => "integer"];

}