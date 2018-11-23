<?php

namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class LogEjecucionParche extends BaseModel
{
    //
  	use SoftDeletes;
    protected $generarID = true;
    protected $guardarIDServidor = true;
    protected $guardarIDUsuario = true;
    protected $fillable = ['clues','tipo_parche','nombre_parche','fecha_liberacion','fecha_ejecucion'];
    protected $table = 'log_ejecucion_parches';
}
