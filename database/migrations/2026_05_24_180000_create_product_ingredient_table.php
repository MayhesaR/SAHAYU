<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('product_ingredient', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('material_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity', 10, 4); // Recipe standard qty
            $table->timestamps();
        });

        // Seed default standard recipes for "Kataji Rasa" if products exist
        $products = DB::table('products')->get();
        $materials = DB::table('materials')->get();

        foreach ($products as $product) {
            $recipe = [];
            if (str_contains($product->name, 'Klasik 2 Rasa')) {
                $recipe = [
                    'Roti Kasur / Roti Terigu' => 1.0,
                    'Margarin / Mentega' => 0.05,
                    'Selai Cokelat Premium' => 0.05,
                    'Selai Matcha / Green Tea' => 0.05,
                    'Susu Kental Manis' => 0.1,
                    'Kemasan Box Roti Kataji' => 1.0,
                ];
            } elseif (str_contains($product->name, 'Full Keju')) {
                $recipe = [
                    'Roti Kasur / Roti Terigu' => 1.0,
                    'Margarin / Mentega' => 0.05,
                    'Keju Cheddar Batangan' => 0.1,
                    'Susu Kental Manis' => 0.15,
                    'Kemasan Box Roti Kataji' => 1.0,
                ];
            } elseif (str_contains($product->name, 'Cokelat Matcha')) {
                $recipe = [
                    'Roti Kasur / Roti Terigu' => 1.0,
                    'Margarin / Mentega' => 0.05,
                    'Selai Cokelat Premium' => 0.08,
                    'Selai Matcha / Green Tea' => 0.08,
                    'Susu Kental Manis' => 0.1,
                    'Kemasan Box Roti Kataji' => 1.0,
                ];
            } elseif (str_contains($product->name, 'Basreng Garing')) {
                $recipe = [
                    'Kemasan Box Roti Kataji' => 1.0,
                ];
            }

            // Also support Toko Roti Seeder products if they exist
            if (str_contains($product->name, 'Roti Cokelat Lumer')) {
                $recipe = [
                    'Tepung Terigu (Premium)' => 0.2,
                    'Gula Pasir' => 0.05,
                    'Mentega (Butter)' => 0.03,
                    'Telur Ayam' => 0.5,
                    'Cokelat Bubuk' => 0.05,
                    'Ragi Instan' => 0.01,
                ];
            } elseif (str_contains($product->name, 'Roti Keju Spesial')) {
                $recipe = [
                    'Tepung Terigu (Premium)' => 0.2,
                    'Gula Pasir' => 0.05,
                    'Mentega (Butter)' => 0.03,
                    'Telur Ayam' => 0.5,
                    'Keju Cheddar' => 0.06,
                    'Ragi Instan' => 0.01,
                ];
            } elseif (str_contains($product->name, 'Croissant Butter')) {
                $recipe = [
                    'Tepung Terigu (Premium)' => 0.25,
                    'Gula Pasir' => 0.03,
                    'Mentega (Butter)' => 0.1,
                    'Telur Ayam' => 0.5,
                    'Ragi Instan' => 0.01,
                ];
            } elseif (str_contains($product->name, 'Brownies Panggang')) {
                $recipe = [
                    'Tepung Terigu (Premium)' => 0.15,
                    'Gula Pasir' => 0.2,
                    'Mentega (Butter)' => 0.12,
                    'Telur Ayam' => 3.0,
                    'Cokelat Bubuk' => 0.15,
                ];
            }

            foreach ($recipe as $materialName => $qty) {
                // Find matching material
                $material = $materials->first(function ($m) use ($materialName) {
                    return str_contains($m->name, $materialName) || str_contains($materialName, $m->name);
                });
                if ($material) {
                    DB::table('product_ingredient')->insert([
                        'company_id' => $product->company_id,
                        'product_id' => $product->id,
                        'material_id' => $material->id,
                        'quantity' => $qty,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_ingredient');
    }
};
