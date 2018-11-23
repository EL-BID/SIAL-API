<?php

namespace App\models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;


class DocumentoSistema extends BaseModel
{
    use SoftDeletes;
    protected $generarID = false;
    protected $guardarIDServidor = false;
    protected $guardarIDUsuario = true;
    //protected $fillable = ["created_at","updated_at"];
    protected $table = 'documentos_sistema';

    public function documentoCargos(){
        return $this->hasMany('App\Models\DocumentoSistemaCargo','documento_sistema_id')->with("cargo");
    }

}
