<?php
namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class SustanciaLaboratorio extends BaseModel{
    
    use SoftDeletes;
    
    protected $generarID = false;
    protected $guardarIDServidor = false;
    protected $guardarIDUsuario = true;
    //public $incrementing = false;
    
    protected $table = 'sustancias_laboratorio';  
    protected $primaryKey = 'insumo_medico_clave';
    //protected $fillable = ["id","insumo_medico_clave","presentacion_id","es_controlado","es_surfactante","concentracion","contenido","cantidad_x_envase","unidad_medida_id","indicaciones","via_administracion_id","dosis"];

    //Este scope carga los datos de catalogos que utiliza medicamentos
    public function scopeConDescripcionSustancia($query){
        return $query->select('sustancias_laboratorio.*',
                            'presentaciones_sustancias.nombre as presentacion_nombre',
                            'unidades_medida.nombre as unidad_medida_nombre',
                            'unidades_medida.clave as unidad_medida_clave')
                ->leftjoin('presentaciones_sustancias','presentaciones_sustancias.id','=','sustancias_laboratorio.presentacion_id')
                ->leftjoin('unidades_medida','unidades_medida.id','=','sustancias_laboratorio.unidad_medida_id');
    }
}