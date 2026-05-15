<?php

namespace Database\Seeders;

use App\Models\Material;
use App\Models\OverheadCost;
use App\Models\Product;
use App\Models\Production;
use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DemoUmkmSeeder extends Seeder
{
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        DB::table('material_stock_movements')->truncate();
        DB::table('product_stock_movements')->truncate();
        DB::table('production_materials')->truncate();
        DB::table('sale_items')->truncate();
        DB::table('sales')->truncate();
        DB::table('productions')->truncate();
        DB::table('overhead_costs')->truncate();
        DB::table('products')->truncate();
        DB::table('materials')->truncate();
        Schema::enableForeignKeyConstraints();

        $materials = collect([
            [
                'key' => 'tepung',
                'name' => 'Tepung Terigu Protein Sedang',
                'category' => 'Dasar',
                'unit' => 'kg',
                'purchase_unit' => 'karung',
                'unit_conversion_factor' => 25,
                'price' => 13000,
                'minimum_stock' => 30,
                'default_supplier' => 'CV Pangan Jaya',
                'supplier_lead_time_days' => 2,
                'opening_stock' => 240,
            ],
            [
                'key' => 'gula',
                'name' => 'Gula Pasir',
                'category' => 'Dasar',
                'unit' => 'kg',
                'purchase_unit' => 'karung',
                'unit_conversion_factor' => 50,
                'price' => 17500,
                'minimum_stock' => 25,
                'default_supplier' => 'UD Sumber Manis',
                'supplier_lead_time_days' => 3,
                'opening_stock' => 180,
            ],
            [
                'key' => 'mentega',
                'name' => 'Mentega Premium',
                'category' => 'Finishing',
                'unit' => 'kg',
                'purchase_unit' => 'karton',
                'unit_conversion_factor' => 20,
                'price' => 46000,
                'minimum_stock' => 18,
                'default_supplier' => 'PT Dairy Nusantara',
                'supplier_lead_time_days' => 4,
                'opening_stock' => 85,
            ],
            [
                'key' => 'cokelat',
                'name' => 'Cokelat Bubuk',
                'category' => 'Finishing',
                'unit' => 'kg',
                'purchase_unit' => 'dus',
                'unit_conversion_factor' => 10,
                'price' => 68000,
                'minimum_stock' => 10,
                'default_supplier' => 'CV Cocoa Prima',
                'supplier_lead_time_days' => 5,
                'opening_stock' => 42,
            ],
            [
                'key' => 'telur',
                'name' => 'Telur Ayam Grade A',
                'category' => 'Struktur',
                'unit' => 'butir',
                'purchase_unit' => 'tray',
                'unit_conversion_factor' => 30,
                'price' => 2200,
                'minimum_stock' => 400,
                'default_supplier' => 'Peternak Makmur',
                'supplier_lead_time_days' => 1,
                'opening_stock' => 2800,
            ],
            [
                'key' => 'kemasan',
                'name' => 'Kemasan Box Premium',
                'category' => 'Finishing',
                'unit' => 'pcs',
                'purchase_unit' => 'pak',
                'unit_conversion_factor' => 50,
                'price' => 1400,
                'minimum_stock' => 500,
                'default_supplier' => 'PT Kemas Rapi',
                'supplier_lead_time_days' => 3,
                'opening_stock' => 4200,
            ],
        ])->mapWithKeys(function (array $item) {
            $material = Material::create([
                'name' => $item['name'],
                'category' => $item['category'],
                'stock' => $item['opening_stock'],
                'minimum_stock' => $item['minimum_stock'],
                'unit' => $item['unit'],
                'purchase_unit' => $item['purchase_unit'],
                'unit_conversion_factor' => $item['unit_conversion_factor'],
                'default_supplier' => $item['default_supplier'],
                'supplier_lead_time_days' => $item['supplier_lead_time_days'],
                'price' => $item['price'],
            ]);

            $material->stockMovements()->create([
                'type' => 'in',
                'quantity' => $item['opening_stock'],
                'stock_before' => 0,
                'stock_after' => $item['opening_stock'],
                'unit_price' => $item['price'],
                'transaction_date' => Carbon::now()->subDays(14)->toDateString(),
                'reference' => 'Saldo awal',
                'note' => 'Pembukaan data awal gudang.',
            ]);

            return [$item['key'] => $material];
        });

        OverheadCost::insert([
            ['name' => 'Listrik Produksi', 'cost' => 550000, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Gas Oven', 'cost' => 320000, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Penyusutan Alat', 'cost' => 250000, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Perawatan Harian', 'cost' => 180000, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $products = collect([
            ['key' => 'nastar', 'name' => 'Nastar Premium 250gr', 'selling_price' => 62000, 'minimum_stock' => 40],
            ['key' => 'kastengel', 'name' => 'Kastengel Keju 250gr', 'selling_price' => 68000, 'minimum_stock' => 35],
            ['key' => 'choco', 'name' => 'Choco Cookies 200gr', 'selling_price' => 56000, 'minimum_stock' => 30],
            ['key' => 'almond', 'name' => 'Almond Crunch 200gr', 'selling_price' => 59000, 'minimum_stock' => 25],
            ['key' => 'mixjar', 'name' => 'Mix Cookies Jar 500gr', 'selling_price' => 98000, 'minimum_stock' => 20],
        ])->mapWithKeys(function (array $item) {
            $product = Product::create([
                'name' => $item['name'],
                'selling_price' => $item['selling_price'],
                'stock' => 0,
                'minimum_stock' => $item['minimum_stock'],
            ]);

            return [$item['key'] => $product];
        });

        $productionPlans = [
            [
                'date' => Carbon::now()->subDays(9),
                'product' => 'nastar',
                'quantity' => 220,
                'reject' => 8,
                'status' => 'done',
                'supervisor' => 'Rina Purnama',
                'labor' => 285000,
                'overhead' => 180000,
                'notes' => 'Batch normal, oven stabil.',
                'materials' => ['tepung' => 36, 'gula' => 20, 'mentega' => 12, 'telur' => 420, 'kemasan' => 220],
            ],
            [
                'date' => Carbon::now()->subDays(8),
                'product' => 'kastengel',
                'quantity' => 180,
                'reject' => 6,
                'status' => 'done',
                'supervisor' => 'Rina Purnama',
                'labor' => 265000,
                'overhead' => 170000,
                'notes' => 'Penyesuaian suhu awal batch.',
                'materials' => ['tepung' => 32, 'gula' => 12, 'mentega' => 14, 'telur' => 360, 'kemasan' => 180],
            ],
            [
                'date' => Carbon::now()->subDays(7),
                'product' => 'choco',
                'quantity' => 210,
                'reject' => 10,
                'status' => 'done',
                'supervisor' => 'Bagas Santoso',
                'labor' => 290000,
                'overhead' => 175000,
                'notes' => 'Tambahan choco chips lot baru.',
                'materials' => ['tepung' => 34, 'gula' => 18, 'mentega' => 11, 'cokelat' => 8, 'telur' => 390, 'kemasan' => 210],
            ],
            [
                'date' => Carbon::now()->subDays(6),
                'product' => 'almond',
                'quantity' => 170,
                'reject' => 5,
                'status' => 'done',
                'supervisor' => 'Bagas Santoso',
                'labor' => 250000,
                'overhead' => 160000,
                'notes' => 'Produksi stabil.',
                'materials' => ['tepung' => 28, 'gula' => 14, 'mentega' => 10, 'telur' => 320, 'kemasan' => 170],
            ],
            [
                'date' => Carbon::now()->subDays(5),
                'product' => 'mixjar',
                'quantity' => 140,
                'reject' => 7,
                'status' => 'done',
                'supervisor' => 'Dina Maharani',
                'labor' => 310000,
                'overhead' => 195000,
                'notes' => 'Batch campuran 3 varian.',
                'materials' => ['tepung' => 30, 'gula' => 16, 'mentega' => 12, 'cokelat' => 5, 'telur' => 300, 'kemasan' => 140],
            ],
            [
                'date' => Carbon::now()->subDays(3),
                'product' => 'nastar',
                'quantity' => 240,
                'reject' => 9,
                'status' => 'done',
                'supervisor' => 'Dina Maharani',
                'labor' => 320000,
                'overhead' => 210000,
                'notes' => 'Target Lebaran, overtime 1 jam.',
                'materials' => ['tepung' => 40, 'gula' => 23, 'mentega' => 13, 'telur' => 450, 'kemasan' => 240],
            ],
            [
                'date' => Carbon::now()->subDay(),
                'product' => 'choco',
                'quantity' => 190,
                'reject' => 0,
                'status' => 'process',
                'supervisor' => 'Bagas Santoso',
                'labor' => 260000,
                'overhead' => 170000,
                'notes' => 'Batch berjalan, pending QC final.',
                'materials' => ['tepung' => 30, 'gula' => 16, 'mentega' => 10, 'cokelat' => 7, 'telur' => 350, 'kemasan' => 190],
            ],
        ];

        foreach ($productionPlans as $index => $plan) {
            $materialCost = 0;

            foreach ($plan['materials'] as $materialKey => $qtyUsed) {
                $material = $materials[$materialKey];
                $materialCost += ((float) $material->price) * $qtyUsed;
            }

            $goodQty = max(0, $plan['quantity'] - $plan['reject']);
            $totalCost = $materialCost + $plan['labor'] + $plan['overhead'];
            $unitHpp = $goodQty > 0 ? ($totalCost / $goodQty) : 0;

            $production = Production::create([
                'batch_code' => 'PRD-'.$plan['date']->format('Ymd').'-'.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT),
                'product_id' => $products[$plan['product']]->id,
                'quantity' => $plan['quantity'],
                'good_quantity' => $goodQty,
                'reject_quantity' => $plan['reject'],
                'supervisor_name' => $plan['supervisor'],
                'production_date' => $plan['date']->toDateString(),
                'status' => $plan['status'],
                'material_cost_snapshot' => $materialCost,
                'labor_cost' => $plan['labor'],
                'overhead_cost_snapshot' => $plan['overhead'],
                'total_cost_snapshot' => $totalCost,
                'unit_hpp_snapshot' => $unitHpp,
                'completed_at' => $plan['status'] === 'done' ? $plan['date']->copy()->setTime(17, 30) : null,
                'notes' => $plan['notes'],
                'created_at' => $plan['date']->copy()->setTime(8, 0),
                'updated_at' => $plan['date']->copy()->setTime(17, 30),
            ]);

            $pivotRows = [];

            foreach ($plan['materials'] as $materialKey => $qtyUsed) {
                $material = $materials[$materialKey];
                $before = (int) $material->stock;
                $after = max(0, $before - $qtyUsed);

                $pivotRows[] = [
                    'production_id' => $production->id,
                    'material_id' => $material->id,
                    'quantity' => $qtyUsed,
                ];

                $material->update(['stock' => $after]);

                $material->stockMovements()->create([
                    'type' => 'out',
                    'quantity' => $qtyUsed,
                    'stock_before' => $before,
                    'stock_after' => $after,
                    'unit_price' => (float) $material->price,
                    'transaction_date' => $plan['date']->toDateString(),
                    'reference' => 'Produksi '.$production->batch_code,
                    'note' => 'Pemakaian bahan untuk batch produksi.',
                    'created_at' => $plan['date']->copy()->setTime(9, 0),
                    'updated_at' => $plan['date']->copy()->setTime(9, 0),
                ]);
            }

            DB::table('production_materials')->insert($pivotRows);

            if ($production->status === 'done' && $goodQty > 0) {
                $product = $products[$plan['product']];
                $before = (int) $product->stock;
                $after = $before + $goodQty;

                $product->update(['stock' => $after]);

                $product->stockMovements()->create([
                    'type' => 'in',
                    'quantity' => $goodQty,
                    'stock_before' => $before,
                    'stock_after' => $after,
                    'unit_price' => (float) $product->selling_price,
                    'transaction_date' => $plan['date']->toDateString(),
                    'reference' => 'Produksi '.$production->batch_code,
                    'note' => 'Barang jadi masuk dari batch produksi.',
                    'created_at' => $plan['date']->copy()->setTime(9, 5),
                    'updated_at' => $plan['date']->copy()->setTime(9, 5),
                ]);
            }
        }

        $restockEvents = [
            ['material' => 'tepung', 'quantity' => 120, 'unit_price' => 13200, 'date' => Carbon::now()->subDays(4), 'reference' => 'PO-0426-001'],
            ['material' => 'gula', 'quantity' => 90, 'unit_price' => 17800, 'date' => Carbon::now()->subDays(2), 'reference' => 'PO-0426-002'],
            ['material' => 'kemasan', 'quantity' => 1500, 'unit_price' => 1425, 'date' => Carbon::now()->subDays(2), 'reference' => 'PO-0426-003'],
        ];

        foreach ($restockEvents as $restock) {
            $material = $materials[$restock['material']];
            $before = (int) $material->stock;
            $after = $before + $restock['quantity'];

            $material->update([
                'stock' => $after,
                'price' => $restock['unit_price'],
            ]);

            $material->stockMovements()->create([
                'type' => 'in',
                'quantity' => $restock['quantity'],
                'stock_before' => $before,
                'stock_after' => $after,
                'unit_price' => $restock['unit_price'],
                'transaction_date' => $restock['date']->toDateString(),
                'reference' => $restock['reference'],
                'note' => 'Restock pembelian bahan baku.',
                'created_at' => $restock['date']->copy()->setTime(10, 15),
                'updated_at' => $restock['date']->copy()->setTime(10, 15),
            ]);
        }

        $salesPlans = [
            ['date' => Carbon::now()->subDays(6), 'customer' => 'Toko Sari Rasa', 'payment' => 'transfer', 'items' => [['nastar', 18], ['kastengel', 12]]],
            ['date' => Carbon::now()->subDays(5), 'customer' => 'Reseller Bunda Ayu', 'payment' => 'qris', 'items' => [['choco', 20], ['almond', 14]]],
            ['date' => Carbon::now()->subDays(4), 'customer' => 'Walk In Customer', 'payment' => 'cash', 'items' => [['mixjar', 10]]],
            ['date' => Carbon::now()->subDays(3), 'customer' => 'Kantin Harmoni', 'payment' => 'transfer', 'items' => [['nastar', 16], ['choco', 9]]],
            ['date' => Carbon::now()->subDays(2), 'customer' => 'Toko Oleh-Oleh Nusantara', 'payment' => 'transfer', 'items' => [['mixjar', 13], ['kastengel', 8]]],
            ['date' => Carbon::now()->subDay(), 'customer' => 'Reseller Maju Jaya', 'payment' => 'qris', 'items' => [['nastar', 15], ['almond', 10]]],
            ['date' => Carbon::now(), 'customer' => 'Walk In Customer', 'payment' => 'cash', 'items' => [['choco', 11], ['kastengel', 7]]],
        ];

        foreach ($salesPlans as $plan) {
            $items = [];
            $total = 0;

            foreach ($plan['items'] as [$productKey, $qty]) {
                $product = $products[$productKey];
                $price = (float) $product->selling_price;
                $lineTotal = $price * $qty;
                $total += $lineTotal;

                $items[] = [
                    'product_id' => $product->id,
                    'quantity' => $qty,
                    'price' => $price,
                ];
            }

            $sale = Sale::create([
                'customer' => $plan['customer'],
                'total' => $total,
                'payment_method' => $plan['payment'],
                'status' => 'paid',
                'created_at' => $plan['date']->copy()->setTime(14, 0),
                'updated_at' => $plan['date']->copy()->setTime(14, 0),
            ]);

            foreach ($items as $item) {
                $product = Product::findOrFail($item['product_id']);
                $before = (int) $product->stock;
                $after = $before - (int) $item['quantity'];

                $product->update(['stock' => $after]);

                $product->stockMovements()->create([
                    'type' => 'out',
                    'quantity' => (int) $item['quantity'],
                    'stock_before' => $before,
                    'stock_after' => $after,
                    'unit_price' => (float) $product->selling_price,
                    'transaction_date' => $plan['date']->toDateString(),
                    'reference' => 'Penjualan #'.$sale->id,
                    'note' => 'Barang jadi keluar karena penjualan.',
                    'created_at' => $plan['date']->copy()->setTime(14, 5),
                    'updated_at' => $plan['date']->copy()->setTime(14, 5),
                ]);

                DB::table('sale_items')->insert([
                    'sale_id' => $sale->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                ]);
            }
        }
    }
}
