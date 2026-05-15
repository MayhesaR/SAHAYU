<?php

namespace App\Models;

use App\Traits\Filterable;
use Illuminate\Database\Eloquent\Model;

class Production extends Model
{
    use Filterable;
    protected $fillable = [
        'batch_code',
        'product_id',
        'quantity',
        'good_quantity',
        'reject_quantity',
        'supervisor_name',
        'production_date',
        'status',
        'material_cost_snapshot',
        'labor_cost',
        'overhead_cost_snapshot',
        'total_cost_snapshot',
        'unit_hpp_snapshot',
        'completed_at',
        'notes',
    ];

    protected $casts = [
        'production_date' => 'date',
        'completed_at' => 'datetime',
        'material_cost_snapshot' => 'decimal:2',
        'labor_cost' => 'decimal:2',
        'overhead_cost_snapshot' => 'decimal:2',
        'total_cost_snapshot' => 'decimal:2',
        'unit_hpp_snapshot' => 'decimal:2',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function materials()
    {
        return $this->belongsToMany(Material::class, 'production_materials')
                    ->withPivot('quantity');
    }
}
