<?php

namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class LogPedidoBorrador extends BaseModel
{
    use SoftDeletes;
    protected $generarID = true;
    protected $guardarIDServidor = true;
    protected $guardarIDUsuario = true;
    protected $table = 'log_pedido_borrador';

    protected $fillable = ["pedido_id", "ip", "usuario_id", "navegador", "accion"];
}
