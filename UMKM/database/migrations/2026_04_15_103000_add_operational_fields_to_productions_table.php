<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productions', function (Blueprint $table) {
            $table->string('batch_code')->nullable()->after('id');
            $table->string('supervisor_name')->nullable()->after('quantity');
            $table->unsignedInteger('good_quantity')->default(0)->after('quantity');
            $table->unsignedInteger('reject_quantity')->default(0)->after('good_quantity');
            $table->decimal('material_cost_snapshot', 12, 2)->default(0)->after('status');
            $table->decimal('labor_cost', 12, 2)->default(0)->after('material_cost_snapshot');
            $table->decimal('overhead_cost_snapshot', 12, 2)->default(0)->after('labor_cost');
            $table->decimal('total_cost_snapshot', 12, 2)->default(0)->after('overhead_cost_snapshot');
            $table->decimal('unit_hpp_snapshot', 12, 2)->default(0)->after('total_cost_snapshot');
            $table->timestamp('completed_at')->nullable()->after('unit_hpp_snapshot');
            $table->text('notes')->nullable()->after('completed_at');

            $table->index('batch_code');
            $table->index(['status', 'production_date']);
        });
    }

    public function down(): void
    {
        Schema::table('productions', function (Blueprint $table) {
            $table->dropIndex(['batch_code']);
            $table->dropIndex(['status', 'production_date']);
            $table->dropColumn([
                'batch_code',
                'supervisor_name',
                'good_quantity',
                'reject_quantity',
                'material_cost_snapshot',
                'labor_cost',
                'overhead_cost_snapshot',
                'total_cost_snapshot',
                'unit_hpp_snapshot',
                'completed_at',
                'notes',
            ]);
        });
    }
};
