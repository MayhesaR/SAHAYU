<?php

namespace App\Models;

use App\Traits\Filterable;
use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    use Filterable, BelongsToCompany;
    protected $fillable = ['company_id', 'customer', 'total', 'payment_method', 'status'];

    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }
}
