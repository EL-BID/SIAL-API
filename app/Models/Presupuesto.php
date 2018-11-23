<?php

namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Presupuesto extends BaseModel
{
    //use SoftDeletes;
    protected $generarID = true;
    protected $guardarIDServidor = true;
    protected $guardarIDUsuario = true;
    protected $fillable = [ "anio", "causes", "no_causes", "material_curacion", "activo"];
    protected $table = 'presupuestos';
}
