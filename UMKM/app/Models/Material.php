<?php

namespace App\Models;

use App\Traits\Filterable;
use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Material extends Model
{
    use Filterable, BelongsToCompany;
    protected $fillable = [
        'company_id',
        'name',
        'category',
        'raw_material_category_id',
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

    public function rawMaterialCategory()
    {
        return $this->belongsTo(RawMaterialCategory::class, 'raw_material_category_id');
    }

    public function productions()
    {
        return $this->belongsToMany(Production::class, 'production_materials')
                    ->withPivot('quantity');
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(MaterialStockMovement::class)->latest('transaction_date')->latest('id');
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_ingredient', 'material_id', 'product_id')
                    ->withPivot('quantity')
                    ->withTimestamps();
    }
}
