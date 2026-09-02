<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RawMaterialCategory extends Model
{
    use BelongsToCompany;

    protected $table = 'raw_material_categories';

    protected $fillable = [
        'company_id',
        'name',
    ];

    /**
     * Relationship: A category can contain many raw materials.
       */
    public function materials(): HasMany
    {
        return $this->hasMany(Material::class, 'raw_material_category_id');
    }
}
