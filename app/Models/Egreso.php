<?php
namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class Egreso extends BaseModel
{
    use SoftDeletes;
    
    protected $generarID = true;
    protected $guardarIDServidor = true;
    protected $guardarIDUsuario = true;
    
    protected $table = 'pacientes_egreso';  
    protected $fillable = ["ingreso_id","motivo_egreso_id","fecha_hora","contrareferencia", "unidad_referencia"];

}
