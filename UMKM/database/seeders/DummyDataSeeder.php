<?php

namespace Database\Seeders;

use App\Models\Material;
use App\Models\Product;
use App\Models\Production;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\OverheadCost;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DummyDataSeeder extends Seeder
{
    public function run(): void
    {
        // 0. Clean Existing Data
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        User::truncate();
        Material::truncate();
        Product::truncate();
        Production::truncate();
        DB::table('production_materials')->truncate();
        OverheadCost::truncate();
        Sale::truncate();
        SaleItem::truncate();
        DB::table('material_stock_movements')->truncate();
        DB::table('product_stock_movements')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // 1. Users
        User::create([
            'name' => 'Admin Mayhesa',
            'email' => 'admin@umkm.com',
            'password' => Hash::make('password'),
            'role' => 'admin'
        ]);

        User::create([
            'name' => 'Staff Produksi',
            'email' => 'staff@umkm.com',
            'password' => Hash::make('password'),
            'role' => 'staff'
        ]);

        // 2. Materials (With Suppliers)
        $tepung = Material::create(['name' => 'Tepung Terigu', 'category' => 'Struktur', 'stock' => 5000, 'unit' => 'Kg', 'price' => 12000, 'minimum_stock' => 50, 'default_supplier' => 'PT Bogasari Utama']);
        $mentega = Material::create(['name' => 'Mentega Wisman', 'category' => 'Dasar', 'stock' => 2000, 'unit' => 'Kg', 'price' => 45000, 'minimum_stock' => 20, 'default_supplier' => 'Global Bakery Supply']);
        $gula = Material::create(['name' => 'Gula Pasir', 'category' => 'Dasar', 'stock' => 3000, 'unit' => 'Kg', 'price' => 15000, 'minimum_stock' => 30, 'default_supplier' => 'Distributor Gula Lokal']);

        // 3. Products
        $rotiTawar = Product::create(['name' => 'Roti Tawar Premium', 'stock' => 5000, 'selling_price' => 25000, 'minimum_stock' => 20, 'image' => 'products/roti_tawar.png']);
        $croissant = Product::create(['name' => 'Croissant Almond', 'stock' => 5000, 'selling_price' => 18000, 'minimum_stock' => 15, 'image' => 'products/croissant.png']);
        $brownies = Product::create(['name' => 'Brownies Lumer', 'stock' => 5000, 'selling_price' => 45000, 'minimum_stock' => 10, 'image' => 'products/brownies.png']);

        // 3b. Seed Standard Recipes (product_ingredient)
        $rotiTawar->ingredients()->attach($tepung->id, ['quantity' => 0.25]);
        $rotiTawar->ingredients()->attach($gula->id, ['quantity' => 0.05]);
        $rotiTawar->ingredients()->attach($mentega->id, ['quantity' => 0.03]);

        $croissant->ingredients()->attach($tepung->id, ['quantity' => 0.2]);
        $croissant->ingredients()->attach($mentega->id, ['quantity' => 0.1]);
        $croissant->ingredients()->attach($gula->id, ['quantity' => 0.03]);

        $brownies->ingredients()->attach($tepung->id, ['quantity' => 0.15]);
        $brownies->ingredients()->attach($gula->id, ['quantity' => 0.2]);
        $brownies->ingredients()->attach($mentega->id, ['quantity' => 0.12]);

        // 4. Time Range: Last 3 Months
        $startDate = Carbon::now()->subMonths(3)->startOfDay();
        $endDate = Carbon::now();

        $currentDate = clone $startDate;
        $batchCounter = 1;

        while ($currentDate->lte($endDate)) {
            // A. Monthly Overhead
            if ($currentDate->day == 1) {
                OverheadCost::create([
                    'name' => 'Sewa Ruko ' . $currentDate->format('F'),
                    'category' => 'Biaya Tetap (Sewa/Gaji)',
                    'cost' => 2000000,
                    'transaction_date' => clone $currentDate
                ]);
                OverheadCost::create([
                    'name' => 'Gaji Karyawan ' . $currentDate->format('F'),
                    'category' => 'Biaya Tetap (Sewa/Gaji)',
                    'cost' => 3500000,
                    'transaction_date' => clone $currentDate
                ]);
            }

            // B. Periodic Overhead
            if ($currentDate->day == 15) {
                OverheadCost::create([
                    'name' => 'Listrik & Air ' . $currentDate->format('F'),
                    'category' => 'Utilitas (Listrik/Air/Gas)',
                    'cost' => rand(400000, 700000),
                    'transaction_date' => clone $currentDate
                ]);
            }

            // C. Production (Every 2 days)
            if ($currentDate->day % 2 == 0) {
                $targetProduct = [$rotiTawar, $croissant, $brownies][rand(0, 2)];
                $qty = rand(40, 80);
                $reject = rand(1, 5);
                
                $prod = Production::create([
                    'batch_code' => 'BATCH-' . str_pad($batchCounter++, 4, '0', STR_PAD_LEFT),
                    'product_id' => $targetProduct->id,
                    'quantity' => $qty,
                    'good_quantity' => $qty - $reject,
                    'reject_quantity' => $reject,
                    'status' => 'done',
                    'production_date' => clone $currentDate,
                    'supervisor_name' => 'Staff Produksi',
                    'labor_cost' => rand(100000, 200000),
                    'overhead_cost_snapshot' => rand(40000, 80000),
                    'material_cost_snapshot' => rand(300000, 600000),
                    'total_cost_snapshot' => rand(600000, 900000),
                    'unit_hpp_snapshot' => rand(8000, 15000),
                    'completed_at' => (clone $currentDate)->addHours(6)
                ]);

                $prod->materials()->attach([
                    $tepung->id => ['quantity' => rand(5, 12)],
                    $mentega->id => ['quantity' => rand(1, 4)],
                    $gula->id => ['quantity' => rand(1, 3)],
                ]);
            }

            // D. Daily Sales
            $numSales = rand(10, 20); // More transactions to ensure data visibility
            if ($currentDate->isWeekend()) $numSales += rand(5, 15);

            for ($i = 0; $i < $numSales; $i++) {
                $p = [$rotiTawar, $croissant, $brownies][rand(0, 2)];
                $q = rand(1, 3);
                
                if ($p->stock >= $q) {
                    $sale = Sale::create([
                        'customer' => null,
                        'total' => $p->selling_price * $q,
                        'payment_method' => ['cash', 'qris', 'transfer'][rand(0, 2)],
                        'status' => 'paid',
                        'created_at' => (clone $currentDate)->addMinutes(rand(480, 1140))
                    ]);

                    SaleItem::create([
                        'sale_id' => $sale->id,
                        'product_id' => $p->id,
                        'quantity' => $q,
                        'price' => $p->selling_price
                    ]);

                    $p->decrement('stock', $q);
                }
            }
            $currentDate->addDay();
        }
    }
}
