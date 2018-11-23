<?php 

namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class PersonalCluesMetadatos extends BaseModel {

	use SoftDeletes;
    protected $generarID = true;
    protected $guardarIDServidor = true;
    protected $guardarIDUsuario = true;

    protected $primaryKey = 'id';
    
    protected $table = 'personal_clues_metadatos'; 

	public function PersonalClues(){
		return $this->belongsTo('App\Models\PersonalClues','personal_clues_id','id');
    }

}
