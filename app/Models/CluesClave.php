<?php
namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class CluesClave extends BaseModel{
    
    use SoftDeletes;
    protected $generarID = true;
    protected $guardarIDServidor = true;
    protected $guardarIDUsuario = true;

    protected $table = 'clues_claves';

    public function turnos(){
        return $this->belongsTo('App\Models\Turno','turno_id');
    }

    public function misClaves(){
      return $this->belongsTo('App\Models\Clave','turno_id')
                  ->join('turnos', 'turnos.id', '=', 'clues_turnos.turno_id');
    }
 

}