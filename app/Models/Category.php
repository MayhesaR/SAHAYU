<?php

namespace App\Models;

use App\Traits\Filterable;
use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use Filterable, BelongsToCompany;

    protected $fillable = ['company_id', 'name'];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
