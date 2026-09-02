<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('overhead_costs', function (Blueprint $table) {
            $table->string('category')->default('Lainnya')->after('name');
            $table->date('transaction_date')->nullable()->after('cost');
        });
        
        // Update existing records to have today's date if null
        \Illuminate\Support\Facades\DB::table('overhead_costs')->whereNull('transaction_date')->update([
            'transaction_date' => now()->toDateString()
        ]);
    }

    public function down(): void
    {
        Schema::table('overhead_costs', function (Blueprint $table) {
            $table->dropColumn(['category', 'transaction_date']);
        });
    }
};
