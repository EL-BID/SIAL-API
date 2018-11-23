<?php

namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class PedidoPresupuestoApartado extends BaseModel
{
    use SoftDeletes;
    protected $generarID = false;
    protected $guardarIDServidor = false;
    protected $guardarIDUsuario = true;
    protected $fillable = [ "clues", "pedido_id","almacen_id","mes", "anio","causes_comprometido", "causes_devengado","no_causes_comprometido", "no_causes_devengado", "material_curacion_comprometido", "material_curacion_devengado"];
    protected $table = 'pedido_presupuesto_apartado';

    public function unidadMedica(){
      return $this->belongsTo('App\Models\UnidadMedica','clues','clues');
    }

    public function pedido(){
      return $this->belongsTo('App\Models\Pedido','pedido_id');
    }
}
