<?php
namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class TablaB extends BaseModel{
    
    use SoftDeletes;
    
    protected $table = 'tabla_b'; 
    protected $fillable = ["campo_1"];
        
}