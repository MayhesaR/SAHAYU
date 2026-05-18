<?php

namespace App\Models;

use App\Traits\Filterable;
use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    use Filterable, BelongsToCompany;
    protected $fillable = ['company_id', 'customer_id', 'customer', 'total', 'payment_method', 'status'];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function getTotalAmountAttribute()
    {
        return $this->total;
    }
}
