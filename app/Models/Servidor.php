<?php
namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class Servidor extends BaseModel{
    
    use SoftDeletes;
    protected $generarID = false;
    protected $guardarIDServidor = false;
    protected $guardarIDUsuario = false;    
    protected $table = 'servidores';  
    protected $fillable = ["id","nombre","secret_key","principal","clues","ip"];  
    // Esto es para que el json responda el tipo de dato correcto por el problema de las comillas 
    protected $casts = ["id"=>"string","nombre"=>"string", "secret_key"=>"string","tiene_internet"=>"boolean", "catalogos_actualizados"=>"boolean", "principal"=>"boolean", "clues"=>"string","ip"=>"string"];
    
}