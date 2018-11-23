<?php

namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class UnidadMedicaPresupuesto extends BaseModel
{
    use SoftDeletes;
    protected $generarID = true;
    protected $guardarIDServidor = true;
    protected $guardarIDUsuario = false;
    protected $fillable = [ "clues", "almacen_id","mes", "anio", "presupuesto_id", "proveedor_id", "causes_autorizado", "causes_modificado", "causes_comprometido", "causes_devengado", "causes_disponible", "no_causes_autorizado", "no_causes_modificado", "no_causes_comprometido", "no_causes_devengado", "no_causes_disponible", "material_curacion_autorizado", "material_curacion_modificado", "material_curacion_comprometido", "material_curacion_devengado", "material_curacion_disponible"];
    protected $table = 'unidad_medica_presupuesto';

    public function unidadMedica(){
      return $this->belongsTo('App\Models\UnidadMedica','clues','clues');
    }

    public function almacen(){
      return $this->belongsTo('App\Models\Almacen','almacen_id','id');
    }
}
