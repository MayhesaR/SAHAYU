<?php

namespace Database\Seeders;

use App\Models\Material;
use App\Models\Product;
use App\Models\Production;
use App\Models\ProductionMaterial;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\OverheadCost;
use App\Models\Company;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TokoRotiSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🥐 Seeding Toko Roti Data...');

        $company = Company::first();
        if (!$company) {
            $company = Company::create(['name' => 'SAHAYU Bakery']);
        }

        $admin = User::first();
        $adminId = $admin ? $admin->id : 1;

        $startDate = Carbon::create(2025, 1, 1);
        $endDate = Carbon::now();
        $totalMonths = $startDate->diffInMonths($endDate);
        $totalDays = $startDate->diffInDays($endDate);

        // 1. Materials
        $materials = [
            ['company_id' => $company->id, 'name' => 'Tepung Terigu (Premium)', 'category' => 'Struktur', 'stock' => 100, 'minimum_stock' => 20, 'unit' => 'kg', 'price' => 15000],
            ['company_id' => $company->id, 'name' => 'Gula Pasir', 'category' => 'Dasar', 'stock' => 50, 'minimum_stock' => 10, 'unit' => 'kg', 'price' => 16000],
            ['company_id' => $company->id, 'name' => 'Mentega (Butter)', 'category' => 'Dasar', 'stock' => 30, 'minimum_stock' => 5, 'unit' => 'kg', 'price' => 50000],
            ['company_id' => $company->id, 'name' => 'Telur Ayam', 'category' => 'Dasar', 'stock' => 200, 'minimum_stock' => 50, 'unit' => 'butir', 'price' => 2000],
            ['company_id' => $company->id, 'name' => 'Cokelat Bubuk', 'category' => 'Finishing', 'stock' => 10, 'minimum_stock' => 2, 'unit' => 'kg', 'price' => 80000],
            ['company_id' => $company->id, 'name' => 'Keju Cheddar', 'category' => 'Finishing', 'stock' => 15, 'minimum_stock' => 3, 'unit' => 'kg', 'price' => 90000],
            ['company_id' => $company->id, 'name' => 'Ragi Instan', 'category' => 'Struktur', 'stock' => 5, 'minimum_stock' => 1, 'unit' => 'kg', 'price' => 120000],
        ];

        foreach ($materials as $materialData) {
            $material = Material::create($materialData);
            
            // Initial Stock Movement
            $material->stockMovements()->create([
                'company_id' => $company->id,
                'user_id' => $adminId,
                'type' => 'in',
                'quantity' => $materialData['stock'],
                'stock_before' => 0,
                'stock_after' => $materialData['stock'],
                'unit_price' => $materialData['price'],
                'transaction_date' => $startDate->toDateString(),
                'reference' => 'Stok Awal',
            ]);
        }

        // 2. Products
        $products = [
            ['company_id' => $company->id, 'name' => 'Roti Cokelat Lumer', 'selling_price' => 12000, 'stock' => 0, 'minimum_stock' => 10, 'image' => 'products/roti_cokelat.png'],
            ['company_id' => $company->id, 'name' => 'Roti Keju Spesial', 'selling_price' => 15000, 'stock' => 0, 'minimum_stock' => 10, 'image' => 'products/roti_keju.png'],
            ['company_id' => $company->id, 'name' => 'Croissant Butter', 'selling_price' => 20000, 'stock' => 0, 'minimum_stock' => 5, 'image' => 'products/croissant.png'],
            ['company_id' => $company->id, 'name' => 'Brownies Panggang', 'selling_price' => 45000, 'stock' => 0, 'minimum_stock' => 5, 'image' => 'products/brownies.png'],
        ];

        $createdProducts = [];
        foreach ($products as $product) {
            $createdProducts[] = Product::create($product);
        }

        // 2b. Seed Standard Recipes (product_ingredient)
        $tempMaterials = Material::where('company_id', $company->id)->get();
        foreach ($createdProducts as $product) {
            $recipe = [];
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
                $material = $tempMaterials->first(function ($m) use ($materialName) {
                    return str_contains($m->name, $materialName) || str_contains($materialName, $m->name);
                });
                if ($material) {
                    $product->ingredients()->attach($material->id, [
                        'quantity' => $qty,
                        'company_id' => $company->id
                    ]);
                }
            }
        }

        // 3. Overhead Costs (From 2025 to Now)
        $overheadCategories = ['Listrik', 'Sewa', 'Gaji', 'Kemasan'];
        for ($i = 0; $i <= $totalMonths; $i++) {
            $date = $startDate->copy()->addMonths($i);
            if ($date->greaterThan($endDate)) break;

            foreach ($overheadCategories as $cat) {
                OverheadCost::create([
                    'company_id' => $company->id,
                    'name' => "Biaya $cat " . $date->format('F Y'),
                    'category' => 'Operasional',
                    'cost' => rand(500000, 2000000),
                    'transaction_date' => $date->copy()->startOfMonth()->addDays(rand(0, min(27, $date->diffInDays($endDate)))),
                ]);
            }
        }
        // 4. Productions & Sales (From 2025 to Now)
        $totalDays = $startDate->diffInDays($endDate);
        $this->command->info("  - Generating data from 2025-01-01 to " . $endDate->toDateString() . " (~$totalDays days)...");
        
        $allMaterials = Material::where('company_id', $company->id)->get();

        for ($i = 0; $i <= $totalDays; $i++) {
            $date = $startDate->copy()->addDays($i);
            
            // Weekly Material Re-supply (Stock In)
            if ($date->dayOfWeek === Carbon::MONDAY) {
                foreach ($allMaterials as $mat) {
                    $supplyQty = rand(50, 150);
                    $before = (int) $mat->stock;
                    $mat->increment('stock', $supplyQty);
                    
                    $mat->stockMovements()->create([
                        'company_id' => $company->id,
                        'user_id' => $adminId,
                        'type' => 'in',
                        'quantity' => $supplyQty,
                        'stock_before' => $before,
                        'stock_after' => $before + $supplyQty,
                        'unit_price' => $mat->price,
                        'transaction_date' => $date->toDateString(),
                        'reference' => 'Restock Mingguan',
                    ]);
                }
            }

            // Randomly decide to produce something today
            if (rand(0, 100) < 75) { 
                $product = $createdProducts[array_rand($createdProducts)];
                $qty = rand(30, 60);
                $reject = rand(0, 4);
                $good = $qty - $reject;

                $production = Production::create([
                    'company_id' => $company->id,
                    'batch_code' => 'PROD-' . $date->format('Ymd') . '-' . strtoupper(substr(md5(uniqid()), 0, 4)),
                    'product_id' => $product->id,
                    'quantity' => $qty,
                    'good_quantity' => $good,
                    'reject_quantity' => $reject,
                    'supervisor_name' => 'Budi Santoso',
                    'production_date' => $date,
                    'status' => 'done',
                    'material_cost_snapshot' => $qty * 5000,
                    'labor_cost' => $qty * 1200,
                    'overhead_cost_snapshot' => $qty * 600,
                    'total_cost_snapshot' => $qty * 6800,
                    'unit_hpp_snapshot' => ($qty * 6800) / max(1, $good),
                    'completed_at' => $date->copy()->addHours(6),
                ]);

                $product->increment('stock', $good);

                // Production Usage (Stock Out)
                $randomMaterials = $allMaterials->random(3);
                foreach ($randomMaterials as $mat) {
                    $useQty = rand(2, 8);
                    if ((int) $mat->stock >= $useQty) {
                        $before = (int) $mat->stock;
                        $mat->decrement('stock', $useQty);

                        ProductionMaterial::create([
                            'company_id' => $company->id,
                            'production_id' => $production->id,
                            'material_id' => $mat->id,
                            'quantity' => $useQty,
                        ]);

                        $mat->stockMovements()->create([
                            'company_id' => $company->id,
                            'user_id' => $adminId,
                            'type' => 'out',
                            'quantity' => $useQty,
                            'stock_before' => $before,
                            'stock_after' => $before - $useQty,
                            'unit_price' => $mat->price,
                            'transaction_date' => $date->toDateString(),
                            'reference' => "Produksi #{$production->batch_code}",
                        ]);
                    }
                }
            }

            // Sales (Daily activity)
            $isWeekend = $date->isWeekend();
            $numSales = $isWeekend ? rand(10, 20) : rand(5, 12);
            
            for ($j = 0; $j < $numSales; $j++) {
                $product = $createdProducts[array_rand($createdProducts)];
                $saleQty = rand(1, 4);
                $total = $saleQty * $product->selling_price;

                $sale = Sale::create([
                    'company_id' => $company->id,
                    'customer' => 'Pelanggan Umum',
                    'total' => $total,
                    'payment_method' => ['Cash', 'QRIS', 'Transfer'][rand(0, 2)],
                    'status' => 'paid',
                    'created_at' => $date->copy()->addHours(rand(8, 21)),
                ]);

                SaleItem::create([
                    'company_id' => $company->id,
                    'sale_id' => $sale->id,
                    'product_id' => $product->id,
                    'quantity' => $saleQty,
                    'price' => $product->selling_price,
                ]);

                if ($product->stock >= $saleQty) {
                    $product->decrement('stock', $saleQty);
                }
            }
        }

        $this->command->info('✅ Toko Roti Seeding Completed!');
    }
}
