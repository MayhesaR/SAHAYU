<?php

namespace App\Models;

use App\Traits\Filterable;
use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaterialStockMovement extends Model
{
    use Filterable, BelongsToCompany;
    protected $fillable = [
        'company_id',
        'material_id',
        'user_id',
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

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
