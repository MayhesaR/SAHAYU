<?php

namespace Database\Seeders;

use App\Models\Material;
use App\Models\Product;
use App\Models\Production;
use App\Models\ProductionMaterial;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\OverheadCost;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TokoRotiSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🥐 Seeding Toko Roti Data...');

        // 1. Materials
        $materials = [
            ['name' => 'Tepung Terigu (Premium)', 'category' => 'Struktur', 'stock' => 100, 'minimum_stock' => 20, 'unit' => 'kg', 'price' => 15000],
            ['name' => 'Gula Pasir', 'category' => 'Dasar', 'stock' => 50, 'minimum_stock' => 10, 'unit' => 'kg', 'price' => 16000],
            ['name' => 'Mentega (Butter)', 'category' => 'Dasar', 'stock' => 30, 'minimum_stock' => 5, 'unit' => 'kg', 'price' => 50000],
            ['name' => 'Telur Ayam', 'category' => 'Dasar', 'stock' => 200, 'minimum_stock' => 50, 'unit' => 'butir', 'price' => 2000],
            ['name' => 'Cokelat Bubuk', 'category' => 'Finishing', 'stock' => 10, 'minimum_stock' => 2, 'unit' => 'kg', 'price' => 80000],
            ['name' => 'Keju Cheddar', 'category' => 'Finishing', 'stock' => 15, 'minimum_stock' => 3, 'unit' => 'kg', 'price' => 90000],
            ['name' => 'Ragi Instan', 'category' => 'Struktur', 'stock' => 5, 'minimum_stock' => 1, 'unit' => 'kg', 'price' => 120000],
        ];

        foreach ($materials as $material) {
            Material::create($material);
        }

        // 2. Products
        $products = [
            ['name' => 'Roti Cokelat Lumer', 'selling_price' => 12000, 'stock' => 0, 'minimum_stock' => 10],
            ['name' => 'Roti Keju Spesial', 'selling_price' => 15000, 'stock' => 0, 'minimum_stock' => 10],
            ['name' => 'Croissant Butter', 'selling_price' => 20000, 'stock' => 0, 'minimum_stock' => 5],
            ['name' => 'Brownies Panggang', 'selling_price' => 45000, 'stock' => 0, 'minimum_stock' => 5],
        ];

        $createdProducts = [];
        foreach ($products as $product) {
            $createdProducts[] = Product::create($product);
        }

        // 3. Overhead Costs (Full 2025 and 2026)
        $startDate = Carbon::create(2025, 1, 1);
        $endDate = Carbon::create(2026, 12, 31);
        $totalMonths = $startDate->diffInMonths($endDate);
        
        $overheadCategories = ['Listrik', 'Sewa', 'Gaji', 'Kemasan'];
        for ($i = 0; $i <= $totalMonths; $i++) {
            $date = $startDate->copy()->addMonths($i);
            foreach ($overheadCategories as $cat) {
                OverheadCost::create([
                    'name' => "Biaya $cat " . $date->format('F Y'),
                    'category' => 'Operasional',
                    'cost' => rand(500000, 2000000),
                    'transaction_date' => $date->copy()->startOfMonth()->addDays(rand(0, 27)),
                ]);
            }
        }

        // 4. Productions & Sales (Full 2025 and 2026)
        $totalDays = $startDate->diffInDays($endDate);
        $this->command->info("  - Generating data from 2025-01-01 to 2026-12-31 (~$totalDays days)...");
        
        for ($i = 0; $i <= $totalDays; $i++) {
            $date = $startDate->copy()->addDays($i);
            
            // Randomly decide to produce something today
            if (rand(0, 100) < 75) { 
                $product = $createdProducts[array_rand($createdProducts)];
                $qty = rand(30, 60);
                $reject = rand(0, 4);
                $good = $qty - $reject;

                $production = Production::create([
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

                $randomMaterials = Material::inRandomOrder()->take(3)->get();
                foreach ($randomMaterials as $mat) {
                    ProductionMaterial::create([
                        'production_id' => $production->id,
                        'material_id' => $mat->id,
                        'quantity' => rand(2, 8),
                    ]);
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
                    'customer' => 'Pelanggan Umum',
                    'total' => $total,
                    'payment_method' => ['Cash', 'QRIS', 'Transfer'][rand(0, 2)],
                    'status' => 'paid',
                    'created_at' => $date->copy()->addHours(rand(8, 21)),
                ]);

                SaleItem::create([
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
