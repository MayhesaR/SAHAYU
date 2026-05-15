<?php

namespace App\Models;

use App\Traits\Filterable;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    use Filterable;
    protected $fillable = ['customer', 'total', 'payment_method', 'status'];

    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }
}
