<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductStockMovement extends Model
{
    use BelongsToCompany;
    protected $fillable = [
        'company_id',
        'product_id',
        'type',
        'quantity',
        'stock_before',
        'stock_after',
        'unit_price',
        'transaction_date',
        'reference',
        'note',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'unit_price' => 'decimal:2',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
