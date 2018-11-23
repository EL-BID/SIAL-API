<?php

namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class Repositorio extends BaseModel
{
    use SoftDeletes;
    protected $generarID = true;
    protected $guardarIDServidor = true;
    protected $guardarIDUsuario = true;
    protected $table = 'repositorio';

    protected $fillable = ["pedido_id", "peso", "nombre_archivo", "ubicacion", "extension", "usuario_deleted_id"];
}
