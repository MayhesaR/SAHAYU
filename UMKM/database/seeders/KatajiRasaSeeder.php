<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Debt;
use App\Models\Material;
use App\Models\OverheadCost;
use App\Models\Product;
use App\Models\Production;
use App\Models\ProductionMaterial;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class KatajiRasaSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🍞 Seeding 1-Year Kataji Rasa Data...');

        DB::beginTransaction();
        try {
            // 1. Setup Company & Users
            $company = Company::firstOrCreate(['name' => 'Kataji Rasa']);

            $admin = User::updateOrCreate(
                ['email' => 'admin@umkm.com'],
                [
                    'company_id' => $company->id,
                    'name' => 'Admin Kataji',
                    'password' => Hash::make('password123'),
                    'role' => 'admin',
                ]
            );

            $staff = User::updateOrCreate(
                ['email' => 'staff@umkm.com'],
                [
                    'company_id' => $company->id,
                    'name' => 'Staff Kataji',
                    'password' => Hash::make('password123'),
                    'role' => 'staff',
                ]
            );

            // Date range: Last 365 Days
            $endDate = Carbon::now();
            $startDate = $endDate->copy()->subDays(365);
            $totalDays = 365;

            // 2. MASTER INGREDIENTS
            $materialsData = [
                ['name' => 'Roti Kasur / Roti Terigu', 'category' => 'Bahan Utama', 'unit' => 'Pcs', 'minimum_stock' => 50, 'price' => 3000],
                ['name' => 'Margarin / Mentega', 'category' => 'Bahan Utama', 'unit' => 'Kg', 'minimum_stock' => 5, 'price' => 25000],
                ['name' => 'Keju Cheddar Batangan', 'category' => 'Topping', 'unit' => 'Kg', 'minimum_stock' => 3, 'price' => 60000],
                ['name' => 'Selai Cokelat Premium', 'category' => 'Topping', 'unit' => 'Kg', 'minimum_stock' => 2, 'price' => 45000],
                ['name' => 'Selai Matcha / Green Tea', 'category' => 'Topping', 'unit' => 'Kg', 'minimum_stock' => 2, 'price' => 55000],
                ['name' => 'Susu Kental Manis', 'category' => 'Pelengkap', 'unit' => 'Kaleng', 'minimum_stock' => 12, 'price' => 12000],
                ['name' => 'Kemasan Box Roti Kataji', 'category' => 'Kemasan', 'unit' => 'Pcs', 'minimum_stock' => 100, 'price' => 1500],
            ];

            $materials = [];
            foreach ($materialsData as $m) {
                // Initialize stock to 0, we will restock over time
                $materials[] = Material::create(array_merge($m, ['company_id' => $company->id, 'stock' => 0]));
            }

            // 3. FINISHED PRODUCTS
            $productsData = [
                ['name' => 'Roti Bakar Klasik 2 Rasa', 'selling_price' => 18000, 'minimum_stock' => 10, 'cogs' => 8000],
                ['name' => 'Roti Bakar Spesial Full Keju', 'selling_price' => 23500, 'minimum_stock' => 10, 'cogs' => 11000],
                ['name' => 'Roti Bakar Premium Cokelat Matcha', 'selling_price' => 25000, 'minimum_stock' => 10, 'cogs' => 12000],
                ['name' => 'Basreng Garing Chili Oil Kataji', 'selling_price' => 15000, 'minimum_stock' => 20, 'cogs' => 6500],
            ];

            $products = [];
            $productCogs = [];
            foreach ($productsData as $p) {
                $cogs = $p['cogs'];
                unset($p['cogs']);
                $product = Product::create(array_merge($p, ['company_id' => $company->id, 'stock' => 0]));
                $productCogs[$product->id] = $cogs;
                $products[] = $product;
            }

            // 4. CUSTOMERS
            $customersData = [
                ['name' => 'Teh Amanda', 'phone' => '08111222333', 'address' => 'Jl. Dipatiukur No 10'],
                ['name' => 'Kang Budi', 'phone' => '08222333444', 'address' => 'Jl. Dago No 25'],
                ['name' => 'Jajang', 'phone' => '08333444555', 'address' => 'Jl. Braga No 12'],
            ];

            $customers = [];
            foreach ($customersData as $c) {
                $customers[] = Customer::create(array_merge($c, ['company_id' => $company->id]));
            }

            // SIMULATION LOOP
            $overdueCount = 0;
            $overdueTargetDays = [360, 355]; // Ensure some are precisely overdue

            for ($day = 0; $day <= $totalDays; $day++) {
                $currentDate = $startDate->copy()->addDays($day);
                $isWeekend = $currentDate->isWeekend();

                // --- MONTHLY EXPENSES (Pay rent/utilities at the start of month) ---
                if ($currentDate->day === 1) {
                    OverheadCost::create(['company_id' => $company->id, 'name' => 'Sewa Tempat & Kebersihan Lapak', 'category' => 'Operasional', 'cost' => 500000, 'transaction_date' => $currentDate->toDateString()]);
                    OverheadCost::create(['company_id' => $company->id, 'name' => 'Gaji Karyawan Stand', 'category' => 'Gaji', 'cost' => 1200000, 'transaction_date' => $currentDate->toDateString()]);
                }

                // --- WEEKLY EXPENSES & MATERIAL RESTOCK ---
                if ($currentDate->dayOfWeek === Carbon::MONDAY) {
                    OverheadCost::create(['company_id' => $company->id, 'name' => 'Isi Ulang Gas Elpiji & Utilitas', 'category' => 'Operasional', 'cost' => 150000, 'transaction_date' => $currentDate->toDateString()]);

                    // Restock materials
                    foreach ($materials as $mat) {
                        $supplyQty = rand(50, 200);
                        if ($mat->name === 'Roti Kasur / Roti Terigu' || $mat->name === 'Kemasan Box Roti Kataji') {
                            $supplyQty = rand(200, 500);
                        }

                        $before = (int) $mat->stock;
                        $mat->stock += $supplyQty;
                        $mat->save();

                        $mat->stockMovements()->create([
                            'company_id' => $company->id,
                            'user_id' => $admin->id,
                            'type' => 'in',
                            'quantity' => $supplyQty,
                            'stock_before' => $before,
                            'stock_after' => $before + $supplyQty,
                            'unit_price' => $mat->price,
                            'transaction_date' => $currentDate->toDateString(),
                            'reference' => 'Restock Gudang Mingguan',
                        ]);
                    }
                }

                // --- DAILY PRODUCTION (Keep product stocks healthy) ---
                foreach ($products as $prod) {
                    if ($prod->stock < ($prod->minimum_stock * 2) || rand(0, 100) > 70) {
                        $produceQty = rand(15, 40);
                        $rejectQty = rand(0, 2);
                        $goodQty = $produceQty - $rejectQty;

                        $cogsRef = $productCogs[$prod->id] ?? 8000;
                        $production = Production::create([
                            'company_id' => $company->id,
                            'batch_code' => 'PRD-' . $currentDate->format('Ymd') . '-' . strtoupper(substr(md5(uniqid()), 0, 4)),
                            'product_id' => $prod->id,
                            'quantity' => $produceQty,
                            'good_quantity' => $goodQty,
                            'reject_quantity' => $rejectQty,
                            'supervisor_name' => $staff->name,
                            'production_date' => $currentDate->toDateString(),
                            'status' => 'done',
                            'material_cost_snapshot' => $produceQty * ($cogsRef * 0.7), // 70% material
                            'labor_cost' => $produceQty * ($cogsRef * 0.2), // 20% labor
                            'overhead_cost_snapshot' => $produceQty * ($cogsRef * 0.1), // 10% overhead
                            'total_cost_snapshot' => $produceQty * $cogsRef,
                            'unit_hpp_snapshot' => $cogsRef,
                            'completed_at' => $currentDate->copy()->addHours(2),
                        ]);

                        $prod->stock += $goodQty;
                        $prod->save();

                        // Deduct random materials
                        $usedMats = collect($materials)->random(3);
                        foreach ($usedMats as $mat) {
                            $useQty = rand(2, 5);
                            if ($mat->stock >= $useQty) {
                                $before = (int) $mat->stock;
                                $mat->stock -= $useQty;
                                $mat->save();

                                ProductionMaterial::create([
                                    'company_id' => $company->id,
                                    'production_id' => $production->id,
                                    'material_id' => $mat->id,
                                    'quantity' => $useQty,
                                ]);

                                $mat->stockMovements()->create([
                                    'company_id' => $company->id,
                                    'user_id' => $staff->id,
                                    'type' => 'out',
                                    'quantity' => $useQty,
                                    'stock_before' => $before,
                                    'stock_after' => $before - $useQty,
                                    'unit_price' => $mat->price,
                                    'transaction_date' => $currentDate->toDateString(),
                                    'reference' => "Masak {$prod->name}",
                                ]);
                            }
                        }
                    }
                }

                // --- DAILY SALES ---
                // Weekends have higher traffic
                $dailyInvoices = $isWeekend ? rand(10, 20) : rand(4, 10);

                for ($i = 0; $i < $dailyInvoices; $i++) {
                    $statusPaid = rand(1, 100) <= 80;
                    $status = $statusPaid ? 'paid' : 'unpaid';
                    $customer = null;

                    if ($status === 'unpaid') {
                        $customer = $customers[array_rand($customers)];
                    }

                    $numItems = rand(1, 3);
                    $totalInvoice = 0;
                    $invoiceItems = [];

                    for ($j = 0; $j < $numItems; $j++) {
                        $prod = $products[array_rand($products)];
                        // Make sure we have stock
                        if ($prod->stock > 0) {
                            $qty = rand(1, min(4, $prod->stock));
                            $subtotal = $qty * $prod->selling_price;
                            $totalInvoice += $subtotal;

                            $invoiceItems[] = [
                                'product_id' => $prod->id,
                                'product_ref' => $prod,
                                'quantity' => $qty,
                                'price' => $prod->selling_price,
                            ];

                            // Update stock in memory immediately to prevent double-booking in the same invoice
                            $prod->stock -= $qty;
                        }
                    }

                    if ($totalInvoice > 0) {
                        $saleTime = $currentDate->copy()->addHours(rand(10, 22));
                        $sale = Sale::create([
                            'company_id' => $company->id,
                            'customer_id' => $customer ? $customer->id : null,
                            'customer' => $customer ? $customer->name : 'Pelanggan Walk-in',
                            'total' => $totalInvoice,
                            'payment_method' => $statusPaid ? (rand(0, 1) ? 'Tunai' : 'Transfer') : 'Tempo',
                            'status' => $status,
                            'created_at' => $saleTime,
                            'updated_at' => $saleTime,
                        ]);

                        foreach ($invoiceItems as $item) {
                            SaleItem::create([
                                'company_id' => $company->id,
                                'sale_id' => $sale->id,
                                'product_id' => $item['product_id'],
                                'quantity' => $item['quantity'],
                                'price' => $item['price'],
                            ]);

                            $item['product_ref']->save();
                        }

                        // Piutang Logic
                        if ($status === 'unpaid') {
                            $dueDate = $currentDate->copy()->addDays(7);

                            // Force some to be overdue (e.g. 4 days ago) near the end of the year simulation
                            if ($overdueCount < 2 && in_array($day, $overdueTargetDays)) {
                                $dueDate = $endDate->copy()->subDays(4);
                                $overdueCount++;
                            }

                            Debt::create([
                                'company_id' => $company->id,
                                'customer_id' => $customer->id,
                                'sale_id' => $sale->id,
                                'total_amount' => $totalInvoice,
                                'remaining_amount' => $totalInvoice,
                                'due_date' => $dueDate->toDateString(),
                                'status' => 'unpaid',
                                'created_at' => $saleTime,
                                'updated_at' => $saleTime,
                            ]);
                        }
                    }
                }
            }

            DB::commit();
            $this->command->info('✅ 1-Year Kataji Rasa Seeding Completed Successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('❌ Seeding Failed: ' . $e->getMessage());
        }
    }
}
