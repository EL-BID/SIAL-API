<?php

namespace App\models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;


class DocumentoSistemaCargo extends BaseModel
{
    use SoftDeletes;
    protected $generarID = false;
    protected $guardarIDServidor = false;
    protected $guardarIDUsuario = true;
    //protected $fillable = ["created_at","updated_at"];
    protected $table = 'documentos_sistema_cargos';

    public function cargo(){
        return $this->belongsTo('App\Models\Cargo','cargo_id');
    }

    public function firmante(){
        return $this->hasOne('App\Models\DocumentoSistemaFirmante','documento_sistema_cargo_id');
    }
}
