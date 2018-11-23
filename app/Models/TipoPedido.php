<?php

namespace App\models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;


class TipoPedido extends BaseModel
{
    use SoftDeletes;
    protected $generarID = true;
    protected $guardarIDServidor = true;
    protected $guardarIDUsuario = true;
    protected $fillable = ["created_at","updated_at"];
    protected $table = 'tipos_pedidos';
}
