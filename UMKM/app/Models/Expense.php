<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    use BelongsToCompany;

    protected $table = 'expenses';

    protected $fillable = [
        'company_id',
        'expense_date',
        'category',
        'amount',
        'description',
    ];

    protected $casts = [
        'expense_date' => 'date',
        'amount' => 'decimal:2',
    ];
}
