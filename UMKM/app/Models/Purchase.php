<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    use BelongsToCompany;

    protected $table = 'purchases';

    protected $fillable = [
        'company_id',
        'purchase_date',
        'total_amount',
        'description',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'total_amount' => 'decimal:2',
    ];
}
