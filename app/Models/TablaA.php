<?php
namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class TablaA extends BaseModel{
    
    use SoftDeletes;
    
    protected $table = 'tabla_a'; 
    protected $fillable = ["campo_1","campo_2"];

}