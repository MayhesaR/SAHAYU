<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductionMaterial extends Model
{
    public $timestamps = false;

    protected $fillable = ['production_id', 'material_id', 'quantity'];
}
