<?php
namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Contracts\Auth\Authenticatable;

class LogInicioSesion extends BaseModel{
    protected $generarID = false;
    protected $guardarIDUsuario = false;
    protected $fillable = ["servidor_id","usuario_id","login_status","ip","navegador","updated_at"];
    protected $table = 'log_inicio_sesion';
    public $timestamps = false;
    
}