<?php
namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class CluesServicio extends BaseModel{
    
    use SoftDeletes;
    protected $generarID = true;
    protected $guardarIDServidor = true;
    protected $guardarIDUsuario = true;

    protected $table = 'clues_servicios';

   // protected $fillable = ["movimiento_id","stock_id", "cantidad", "precio_unitario", "iva", "precio_total"];


    public function servicios(){
        return $this->belongsTo('App\Models\Servicios','servicio_id');
    }

    public function misServicios(){
      return $this->belongsTo('App\Models\Servicios','servicio_id')
                  ->join('servicios', 'servicios.id', '=', 'clues_servicios.servicio_id');
    }
 

}