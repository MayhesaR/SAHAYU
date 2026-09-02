<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Relations\Pivot;

class ProductionMaterial extends Pivot
{
    use BelongsToCompany;
    public $timestamps = false;
    protected $table = 'production_materials';

    protected $fillable = ['company_id', 'production_id', 'material_id', 'quantity'];
}
