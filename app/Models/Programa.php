<?php

namespace App\models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;


class Programa extends BaseModel
{
    use SoftDeletes;
    protected $generarID = false;
    protected $guardarIDServidor = false;
    protected $guardarIDUsuario = true;
    //protected $fillable = ["created_at","updated_at"];
    protected $table = 'programas';
}
