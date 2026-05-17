<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('debts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('sale_id')->nullable();
            $table->decimal('total_amount', 15, 2);
            $table->decimal('remaining_amount', 15, 2);
            $table->date('due_date');
            $table->enum('status', ['unpaid', 'partial', 'paid'])->default('unpaid');
            $table->timestamps();

            // Setup foreign keys
            if (Schema::hasTable('companies')) {
                $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            }
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
            
            if (Schema::hasTable('sales')) {
                $table->foreign('sale_id')->references('id')->on('sales')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('debts');
    }
};
