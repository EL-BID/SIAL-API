<?php

namespace App\models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;


class TipoInsumo extends BaseModel
{
	use SoftDeletes;
    protected $generarID = false;
    protected $guardarIDServidor = false;
    protected $guardarIDUsuario = false;
    protected $fillable = ["id","clave","nombre","created_at","updated_at"];
    protected $table = 'tipos_insumos';
}
