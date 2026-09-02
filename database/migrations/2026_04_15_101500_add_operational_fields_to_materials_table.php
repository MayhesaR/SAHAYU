<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('materials', function (Blueprint $table) {
            $table->unsignedInteger('minimum_stock')->default(10)->after('stock');
            $table->string('default_supplier')->nullable()->after('unit');
        });
    }

    public function down(): void
    {
        Schema::table('materials', function (Blueprint $table) {
            $table->dropColumn(['minimum_stock', 'default_supplier']);
        });
    }
};
