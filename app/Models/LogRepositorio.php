<?php

namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class LogRepositorio extends BaseModel
{
    use SoftDeletes;
    protected $generarID = true;
    protected $guardarIDServidor = true;
    protected $guardarIDUsuario = true;
    protected $table = 'log_repositorio';

    protected $fillable = ["repositorio_id", "ip", "usuario_id", "navegador", "accion"];

}
