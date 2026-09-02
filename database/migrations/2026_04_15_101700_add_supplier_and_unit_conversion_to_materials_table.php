<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('materials', function (Blueprint $table) {
            $table->string('purchase_unit')->nullable()->after('unit');
            $table->decimal('unit_conversion_factor', 12, 4)->default(1)->after('purchase_unit');
            $table->unsignedSmallInteger('supplier_lead_time_days')->nullable()->after('default_supplier');
        });
    }

    public function down(): void
    {
        Schema::table('materials', function (Blueprint $table) {
            $table->dropColumn(['purchase_unit', 'unit_conversion_factor', 'supplier_lead_time_days']);
        });
    }
};
