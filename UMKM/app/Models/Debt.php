<?php

namespace App\Models;

use App\Traits\Filterable;
use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class Debt extends Model
{
    use Filterable, BelongsToCompany;

    protected $fillable = [
        'company_id', 
        'customer_id', 
        'sale_id', 
        'total_amount', 
        'remaining_amount', 
        'due_date', 
        'status'
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function payments()
    {
        return $this->hasMany(DebtPayment::class);
    }
}
