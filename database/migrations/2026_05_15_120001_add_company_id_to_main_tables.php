<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = [
            'users',
            'materials',
            'products',
            'productions',
            'sales',
            'overhead_costs',
            'material_stock_movements',
            'product_stock_movements',
        ];

        foreach ($tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                // Use nullable initially if there is existing data, 
                // but since we usually migrate:fresh, let's make it constrained.
                $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        $tables = [
            'users',
            'materials',
            'products',
            'productions',
            'sales',
            'overhead_costs',
            'material_stock_movements',
            'product_stock_movements',
        ];

        foreach ($tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropConstrainedForeignId('company_id');
            });
        }
    }
};
