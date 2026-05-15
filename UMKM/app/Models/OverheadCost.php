<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OverheadCost extends Model
{
    protected $fillable = ['name', 'category', 'cost', 'transaction_date'];

    protected $casts = [
        'transaction_date' => 'date',
    ];
}
