<?php

namespace Database\Seeders;

use App\Models\Material;
use App\Models\OverheadCost;
use App\Models\Product;
use App\Models\Production;
use App\Models\ProductionMaterial;
use App\Models\Sale;
use App\Models\SaleItem;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * BakeryCaseStudySeeder
 *
 * Populates the database with realistic bakery ("Toko Roti") data
 * spanning January 2026 – May 2026 for university presentation.
 *
 * Run: php artisan db:seed --class=BakeryCaseStudySeeder
 */
class BakeryCaseStudySeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🍞 Seeding Bakery Case Study Data (Jan–May 2026)...');

        // ─── 1. MATERIALS (Bahan Baku) ──────────────────────────
        $materialsData = [
            ['name' => 'Tepung Terigu Protein Tinggi', 'category' => 'Struktur', 'stock' => 250, 'minimum_stock' => 50, 'unit' => 'kg', 'purchase_unit' => 'Karung (25kg)', 'unit_conversion_factor' => 25, 'default_supplier' => 'PT Bogasari', 'supplier_lead_time_days' => 3, 'price' => 12500],
            ['name' => 'Gula Pasir',                  'category' => 'Dasar', 'stock' => 100, 'minimum_stock' => 20, 'unit' => 'kg', 'purchase_unit' => 'Karung (50kg)', 'unit_conversion_factor' => 50, 'default_supplier' => 'UD Manis Jaya', 'supplier_lead_time_days' => 2, 'price' => 14500],
            ['name' => 'Mentega Wisman',               'category' => 'Dasar', 'stock' => 60,  'minimum_stock' => 10, 'unit' => 'kg', 'purchase_unit' => 'Karton (10kg)', 'unit_conversion_factor' => 10, 'default_supplier' => 'CV Dairy Indo', 'supplier_lead_time_days' => 4, 'price' => 42000],
            ['name' => 'Telur Ayam Segar',             'category' => 'Dasar', 'stock' => 300, 'minimum_stock' => 60, 'unit' => 'butir', 'purchase_unit' => 'Tray (30 butir)', 'unit_conversion_factor' => 30, 'default_supplier' => 'Peternakan Harapan', 'supplier_lead_time_days' => 1, 'price' => 2300],
            ['name' => 'Susu Cair Full Cream',         'category' => 'Dasar', 'stock' => 40,  'minimum_stock' => 10, 'unit' => 'liter', 'purchase_unit' => 'Karton (12L)', 'unit_conversion_factor' => 12, 'default_supplier' => 'PT Frisian Flag', 'supplier_lead_time_days' => 3, 'price' => 18000],
            ['name' => 'Cokelat Compound',             'category' => 'Finishing', 'stock' => 30,  'minimum_stock' => 5,  'unit' => 'kg', 'purchase_unit' => 'Blok (5kg)', 'unit_conversion_factor' => 5, 'default_supplier' => 'Tulip Chocolate', 'supplier_lead_time_days' => 5, 'price' => 55000],
            ['name' => 'Ragi Instan',                  'category' => 'Struktur', 'stock' => 10,  'minimum_stock' => 2,  'unit' => 'kg', 'purchase_unit' => 'Pack (500g)', 'unit_conversion_factor' => 0.5, 'default_supplier' => 'Fermipan', 'supplier_lead_time_days' => 3, 'price' => 48000],
            ['name' => 'Keju Cheddar',                 'category' => 'Finishing', 'stock' => 20,  'minimum_stock' => 5,  'unit' => 'kg', 'purchase_unit' => 'Blok (2kg)', 'unit_conversion_factor' => 2, 'default_supplier' => 'Kraft Foods', 'supplier_lead_time_days' => 3, 'price' => 85000],
        ];

        $materials = [];
        foreach ($materialsData as $mat) {
            $materials[$mat['name']] = Material::create($mat);
        }
        $this->command->info('  ✓ 8 materials created');

        // ─── 2. PRODUCTS (Produk Jadi) ──────────────────────────
        $productsData = [
            ['name' => 'Roti Manis Isi Cokelat',  'selling_price' => 8000,  'stock' => 0, 'minimum_stock' => 20],
            ['name' => 'Kue Sus Craquelin',        'selling_price' => 12000, 'stock' => 0, 'minimum_stock' => 15],
            ['name' => 'Macaroni Schotel',         'selling_price' => 35000, 'stock' => 0, 'minimum_stock' => 8],
            ['name' => 'Brownies Panggang',        'selling_price' => 28000, 'stock' => 0, 'minimum_stock' => 10],
            ['name' => 'Croissant Butter',         'selling_price' => 15000, 'stock' => 0, 'minimum_stock' => 12],
        ];

        $products = [];
        foreach ($productsData as $prod) {
            $products[] = Product::create($prod);
        }
        $this->command->info('  ✓ 5 products created');

        // ─── 3. OVERHEAD COSTS (Biaya Operasional Bulanan) ──────
        $overheadNames = [
            ['name' => 'Listrik & Gas LPG',      'category' => 'Utilitas'],
            ['name' => 'Sewa Tempat Usaha',       'category' => 'Sewa'],
            ['name' => 'Gaji Karyawan (2 orang)', 'category' => 'Tenaga Kerja'],
            ['name' => 'Kemasan & Packaging',     'category' => 'Operasional'],
            ['name' => 'Transport & Delivery',    'category' => 'Operasional'],
        ];

        // Monthly overhead variations (slight increases over time simulate inflation)
        $monthlyOverheadBase = [
            '2026-01' => 1.00,
            '2026-02' => 1.02,
            '2026-03' => 1.03, // Ramadhan prep
            '2026-04' => 1.08, // Eid season
            '2026-05' => 1.04,
        ];

        $overheadBaseCosts = [650000, 2500000, 5200000, 380000, 250000];

        foreach ($monthlyOverheadBase as $ym => $factor) {
            $monthDate = Carbon::createFromFormat('Y-m', $ym);
            foreach ($overheadNames as $idx => $oh) {
                $variance = rand(-5, 8) / 100; // -5% to +8% random variance
                $cost = round($overheadBaseCosts[$idx] * $factor * (1 + $variance), -3); // round to nearest 1000

                OverheadCost::create([
                    'name' => $oh['name'],
                    'category' => $oh['category'],
                    'cost' => $cost,
                    'transaction_date' => $monthDate->copy()->addDays(rand(1, 5)),
                    'created_at' => $monthDate->copy()->addDays(rand(1, 5)),
                    'updated_at' => $monthDate->copy()->addDays(rand(1, 5)),
                ]);
            }
        }
        $this->command->info('  ✓ 25 overhead cost entries created (5 months × 5 categories)');

        // ─── 4. PRODUCTION BATCHES + SALES ──────────────────────
        // Monthly seasonality multipliers (simulates real bakery trends)
        $monthSeasonality = [
            1 => 0.85,  // Januari — pasca liburan, agak sepi
            2 => 0.92,  // Februari — Valentine boost
            3 => 1.05,  // Maret — Ramadhan prep, demand naik
            4 => 1.25,  // April — Lebaran season, puncak penjualan
            5 => 0.95,  // Mei — normalisasi
        ];

        // Product-specific production profiles
        // [baseQtyPerBatch, baseMaterialCostPerUnit, baseRejectPct, avgBatchesPerWeek]
        $productProfiles = [
            0 => ['baseQty' => 60,  'matCostPerUnit' => 3200,  'baseReject' => 4.0, 'batchPerWeek' => 3], // Roti Manis
            1 => ['baseQty' => 40,  'matCostPerUnit' => 4500,  'baseReject' => 6.0, 'batchPerWeek' => 2], // Kue Sus
            2 => ['baseQty' => 15,  'matCostPerUnit' => 14000, 'baseReject' => 3.0, 'batchPerWeek' => 2], // Mac Schotel
            3 => ['baseQty' => 20,  'matCostPerUnit' => 11000, 'baseReject' => 2.5, 'batchPerWeek' => 2], // Brownies
            4 => ['baseQty' => 30,  'matCostPerUnit' => 6500,  'baseReject' => 5.5, 'batchPerWeek' => 2], // Croissant
        ];

        $supervisors = ['Pak Hadi', 'Bu Sari', 'Pak Joko'];
        $customers = ['Walk-in Customer', 'Kafe Kopi Nusantara', 'Toko Oleh-Oleh Citra', 'Kantin Universitas', 'Pesanan Catering', 'Grab/GoFood', 'Reseller Ibu Yuni'];
        $paymentMethods = ['cash', 'transfer', 'qris'];

        $batchCounter = 0;
        $totalProductions = 0;
        $totalSales = 0;

        // Iterate over each month
        foreach ($monthSeasonality as $month => $seasonFactor) {
            $monthStart = Carbon::create(2026, $month, 1);
            $monthEnd = ($month == 5)
                ? Carbon::create(2026, 5, 12) // Stop at May 12 (yesterday)
                : $monthStart->copy()->endOfMonth();

            $daysInPeriod = $monthStart->diffInDays($monthEnd) + 1;

            // For each product
            foreach ($products as $prodIdx => $product) {
                $profile = $productProfiles[$prodIdx];

                // Calculate how many batches this month
                $weeksInMonth = max(1, $daysInPeriod / 7);
                $totalBatches = round($profile['batchPerWeek'] * $weeksInMonth * $seasonFactor);

                // Spread batches across the month
                for ($b = 0; $b < $totalBatches; $b++) {
                    $batchCounter++;
                    $batchDate = $monthStart->copy()->addDays(rand(0, $daysInPeriod - 1));

                    // Quantity with ±15% variance
                    $qtyVariance = rand(-15, 15) / 100;
                    $quantity = max(5, round($profile['baseQty'] * $seasonFactor * (1 + $qtyVariance)));

                    // Reject rate: mostly near baseReject, occasionally spikes
                    $isFailedBatch = rand(1, 100) <= 8; // 8% chance of a bad batch
                    if ($isFailedBatch) {
                        $rejectPct = rand(12, 22) / 100; // 12-22% reject for bad batches
                    } else {
                        $rejectPct = max(0, ($profile['baseReject'] + rand(-20, 15) / 10)) / 100;
                    }

                    $rejectQty = max(0, round($quantity * $rejectPct));
                    $goodQty = $quantity - $rejectQty;

                    // Cost calculations (material cost fluctuates ±8%)
                    $costVariance = rand(-8, 8) / 100;
                    $materialCost = round($profile['matCostPerUnit'] * $quantity * (1 + $costVariance));

                    // Labor: Rp 800-1200 per unit
                    $laborCost = round($quantity * rand(800, 1200));

                    // Overhead allocated per batch (simplified)
                    $overheadCost = round($quantity * rand(400, 700));

                    $totalCost = $materialCost + $laborCost + $overheadCost;
                    $unitHpp = $goodQty > 0 ? round($totalCost / $goodQty, 2) : 0;

                    $batchCode = 'BKR-' . str_pad($batchCounter, 4, '0', STR_PAD_LEFT);

                    $production = Production::create([
                        'batch_code' => $batchCode,
                        'product_id' => $product->id,
                        'quantity' => $quantity,
                        'good_quantity' => $goodQty,
                        'reject_quantity' => $rejectQty,
                        'supervisor_name' => $supervisors[array_rand($supervisors)],
                        'production_date' => $batchDate->toDateString(),
                        'status' => 'done',
                        'material_cost_snapshot' => $materialCost,
                        'labor_cost' => $laborCost,
                        'overhead_cost_snapshot' => $overheadCost,
                        'total_cost_snapshot' => $totalCost,
                        'unit_hpp_snapshot' => $unitHpp,
                        'completed_at' => $batchDate->copy()->addHours(rand(4, 10)),
                        'notes' => $isFailedBatch ? 'Batch bermasalah: adonan gagal mengembang' : null,
                        'created_at' => $batchDate,
                        'updated_at' => $batchDate,
                    ]);

                    // Link 2-4 materials to this production
                    $materialKeys = array_keys($materials);
                    $selectedMaterials = array_rand($materials, rand(2, min(4, count($materials))));
                    if (!is_array($selectedMaterials)) $selectedMaterials = [$selectedMaterials];

                    foreach ($selectedMaterials as $matKey) {
                        $mat = $materials[$materialKeys[$matKey]] ?? $materials[array_values($materials)[0]->name] ?? null;
                        if (!$mat) continue;

                        ProductionMaterial::create([
                            'production_id' => $production->id,
                            'material_id' => $mat->id,
                            'quantity' => round($quantity * (rand(5, 30) / 100), 2), // Proportional usage
                        ]);
                    }

                    $totalProductions++;

                    // ─── SALES from this batch ───
                    // Sell 70-95% of good quantity (not everything sells immediately)
                    $sellRatio = rand(70, 95) / 100;
                    $unitsToSell = max(1, round($goodQty * $sellRatio));
                    $remainingToSell = $unitsToSell;

                    // Split into 1-4 sales transactions from this batch
                    $salesCount = min($remainingToSell, rand(1, 4));

                    for ($s = 0; $s < $salesCount && $remainingToSell > 0; $s++) {
                        $saleQty = ($s == $salesCount - 1)
                            ? $remainingToSell
                            : rand(1, max(1, intval($remainingToSell / 2)));
                        $saleQty = min($saleQty, $remainingToSell);
                        $remainingToSell -= $saleQty;

                        $saleDate = $batchDate->copy()->addDays(rand(0, 2)); // Sold within 0-2 days
                        if ($saleDate->greaterThan(Carbon::now())) {
                            $saleDate = Carbon::now()->subHours(rand(1, 12));
                        }

                        $lineTotal = $saleQty * $product->selling_price;

                        $sale = Sale::create([
                            'customer' => $customers[array_rand($customers)],
                            'total' => $lineTotal,
                            'payment_method' => $paymentMethods[array_rand($paymentMethods)],
                            'status' => 'paid',
                            'created_at' => $saleDate,
                            'updated_at' => $saleDate,
                        ]);

                        SaleItem::create([
                            'sale_id' => $sale->id,
                            'product_id' => $product->id,
                            'quantity' => $saleQty,
                            'price' => $product->selling_price,
                        ]);

                        $totalSales++;
                    }

                    // Update product stock with remaining unsold good units
                    $unsold = $goodQty - $unitsToSell;
                    if ($unsold > 0) {
                        $product->increment('stock', $unsold);
                    }
                }
            }

            $this->command->info("  ✓ Month {$month}/2026 seeded (season factor: {$seasonFactor}x)");
        }

        $this->command->info('');
        $this->command->info("🎉 Bakery Case Study Seeding Complete!");
        $this->command->info("   → {$totalProductions} production batches");
        $this->command->info("   → {$totalSales} sale transactions");
        $this->command->info("   → 25 overhead cost entries");
        $this->command->info("   → 8 raw materials, 5 finished products");
    }
}
