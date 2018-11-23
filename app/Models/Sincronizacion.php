<?php
namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sincronizacion extends BaseModel{    
    
    
    protected $generarID = false;
    protected $guardarIDServidor = false;
    protected $guardarIDUsuario = true;    
    public $incrementing = true;
    
    protected $table = 'sincronizaciones';
    protected $fillable = ["fecha_generacion"];    
    
}