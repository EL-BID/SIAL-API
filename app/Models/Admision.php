<?php
namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class Admision extends BaseModel
{
    use SoftDeletes;
    
    protected $generarID = true;
    protected $guardarIDServidor = true;
    protected $guardarIDUsuario = true;
    
    protected $table = 'pacientes_admision';  
    protected $fillable = ["paciente_id","referido", "unidad_referido", "urgencia_calificada", "registro_triage","estado_triage_id","grado_lesion_id", "estatus_ingreso_id", "clues", "fecha_hora_ingreso"];

    
    public function GradoLesion(){
      return $this->belongsTo('App\Models\GradoLesion','grado_lesion_id');
    }

    public function EstadoTriage(){
      return $this->belongsTo('App\Models\EstadoTriage','estado_triage_id');
    }

    public function Unidad(){
      return $this->belongsTo('App\Models\UnidadMedica','clues');
    }

    public function MotivoEgreso(){
      return $this->belongsTo('App\Models\MotivoEgreso','motivo_egreso_id');
    }
    
}
