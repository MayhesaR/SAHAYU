<?php

namespace App\Models;

use App\Traits\Filterable;
use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use Filterable, BelongsToCompany;

    protected $fillable = ['company_id', 'name', 'phone', 'address'];

    public function debts()
    {
        return $this->hasMany(Debt::class);
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }
}
