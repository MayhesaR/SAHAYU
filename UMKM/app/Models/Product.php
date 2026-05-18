<?php

namespace App\Models;

use App\Traits\Filterable;
use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use Filterable, BelongsToCompany;
    protected $fillable = ['company_id', 'name', 'image', 'category_id', 'selling_price', 'stock', 'minimum_stock'];

    protected $casts = [
        'selling_price' => 'decimal:2',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function productions()
    {
        return $this->hasMany(Production::class);
    }

    public function saleItems()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(ProductStockMovement::class)->latest('transaction_date')->latest('id');
    }

    public function getPriceAttribute()
    {
        return $this->selling_price;
    }

    public function getBaseHppAttribute()
    {
        $companyId = auth()->user()->company_id ?? $this->company_id;
        $avg = Production::where('product_id', $this->id)
            ->where('status', 'done')
            ->where('company_id', $companyId)
            ->avg('unit_hpp_snapshot');
            
        return $avg ? (float) $avg : 0.0;
    }
}
