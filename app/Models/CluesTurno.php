<?php
namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class CluesTurno extends BaseModel{
    
    use SoftDeletes;
    protected $generarID = true;
    protected $guardarIDServidor = true;
    protected $guardarIDUsuario = true;

    protected $table = 'clues_turnos';

    public function turnos(){
        return $this->belongsTo('App\Models\Turno','turno_id');
    }

    public function misTurnos(){
      return $this->belongsTo('App\Models\Turno','turno_id')
                  ->join('turnos', 'turnos.id', '=', 'clues_turnos.turno_id');
    }
 

}