<?php

namespace App\Models;

use App\Traits\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Material extends Model
{
    use Filterable;
    protected $fillable = [
        'name',
        'category',
        'stock',
        'minimum_stock',
        'unit',
        'purchase_unit',
        'unit_conversion_factor',
        'default_supplier',
        'supplier_lead_time_days',
        'price',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'unit_conversion_factor' => 'decimal:4',
    ];

    public function productions()
    {
        return $this->belongsToMany(Production::class, 'production_materials')
                    ->withPivot('quantity');
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(MaterialStockMovement::class)->latest('transaction_date')->latest('id');
    }
}
