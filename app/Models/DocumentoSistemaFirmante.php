<?php

namespace App\models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;


class DocumentoSistemaFirmante extends BaseModel
{
    use SoftDeletes;
    protected $generarID = true;
    protected $guardarIDServidor = true;
    protected $guardarIDUsuario = true;

    protected $table = 'documentos_sistema_firmantes';
}
