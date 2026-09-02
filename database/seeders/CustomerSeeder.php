<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Debt;
use App\Models\DebtPayment;
use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::first();
        if (!$company) {
            $company = Company::create(['name' => 'SAHAYU Bakery']);
        }

        // 1. Seed Premium Mock Customers
        $customers = [
            [
                'company_id' => $company->id, 
                'name' => 'Toko Sinar Jaya (Pak Budi)', 
                'phone' => '081234567890', 
                'address' => 'Jl. Pahlawan Raya No. 42, Semarang'
            ],
            [
                'company_id' => $company->id, 
                'name' => 'Warung Bu Ani', 
                'phone' => '082345678901', 
                'address' => 'Pasar Peterongan Kios B-12'
            ],
            [
                'company_id' => $company->id, 
                'name' => 'Bpk. Heri (Karyawan)', 
                'phone' => '089876543210', 
                'address' => 'Mess Karyawan Belakang Ruko'
            ],
        ];

        $customerModels = [];
        foreach ($customers as $c) {
            $customerModels[] = Customer::create($c);
        }

        // 2. Seed Real-time Mock Debts & Installment Payments
        // Debt 1: Sinar Jaya - total 1.000.000, paid 400.000, remaining 600.000 (status: partial)
        $debt1 = Debt::create([
            'company_id' => $company->id,
            'customer_id' => $customerModels[0]->id,
            'total_amount' => 1000000,
            'remaining_amount' => 600000,
            'due_date' => Carbon::now()->addDays(7)->toDateString(),
            'status' => 'partial'
        ]);

        DebtPayment::create([
            'debt_id' => $debt1->id,
            'payment_date' => Carbon::now()->subDays(3)->toDateString(),
            'amount_paid' => 400000,
            'payment_method' => 'transfer'
        ]);

        // Debt 2: Bu Ani - total 350.000, paid 0, remaining 350.000 (status: unpaid - overdue)
        Debt::create([
            'company_id' => $company->id,
            'customer_id' => $customerModels[1]->id,
            'total_amount' => 350000,
            'remaining_amount' => 350000,
            'due_date' => Carbon::now()->subDays(2)->toDateString(),
            'status' => 'unpaid'
        ]);

        // Debt 3: Pak Heri - total 500.000, paid 0, remaining 500.000 (status: unpaid)
        Debt::create([
            'company_id' => $company->id,
            'customer_id' => $customerModels[2]->id,
            'total_amount' => 500000,
            'remaining_amount' => 500000,
            'due_date' => Carbon::now()->addDays(26)->toDateString(),
            'status' => 'unpaid'
        ]);
    }
}
