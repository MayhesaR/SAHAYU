<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('material_stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['in', 'out', 'adjustment']);
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('stock_before');
            $table->unsignedInteger('stock_after');
            $table->decimal('unit_price', 12, 2)->nullable();
            $table->date('transaction_date');
            $table->string('reference')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['material_id', 'transaction_date']);
            $table->index(['type', 'transaction_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('material_stock_movements');
    }
};
