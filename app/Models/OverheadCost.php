<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class OverheadCost extends Model
{
    use BelongsToCompany;
    protected $fillable = ['company_id', 'name', 'category', 'cost', 'transaction_date'];

    protected $casts = [
        'transaction_date' => 'date',
    ];
}
