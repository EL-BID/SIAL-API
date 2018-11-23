<?php

namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class LogRecepcionBorrador extends BaseModel
{
    use SoftDeletes;
    protected $generarID = true;
    protected $guardarIDServidor = true;
    protected $guardarIDUsuario = true;
    protected $table = 'log_recepcion_borrador';

    protected $fillable = ["movimiento_id", "ip", "usuario_id", "navegador", "accion"];
}
