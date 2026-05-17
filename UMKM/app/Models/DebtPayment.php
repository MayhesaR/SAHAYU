<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DebtPayment extends Model
{
    protected $fillable = ['debt_id', 'payment_date', 'amount_paid', 'payment_method'];

    public function debt()
    {
        return $this->belongsTo(Debt::class);
    }
}
