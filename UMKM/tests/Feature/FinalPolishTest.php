<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinalPolishTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_renders_custom_404_error_page_with_correct_aesthetics()
    {
        $response = $this->get('/non-existent-page-url');

        $response->assertStatus(404);
        $response->assertSee('404');
        $response->assertSee('Ups! Halaman tidak ditemukan');
        $response->assertSee('Kembali ke Dashboard Utama');
    }

    public function test_it_requires_authentication_for_database_backup_route()
    {
        $response = $this->get(route('settings.backup'));

        $response->assertRedirect('/login');
    }

    public function test_it_allows_authenticated_user_to_download_database_backup_stream()
    {
        $company = Company::create([
            'name' => 'SAHAYU Test Bakery',
        ]);

        $user = User::create([
            'name' => 'Test Admin',
            'email' => 'admin@sahayu.com',
            'password' => bcrypt('password123'),
            'company_id' => $company->id,
            'role' => 'admin',
        ]);

        $response = $this->actingAs($user)->get(route('settings.backup'));

        $response->assertStatus(200);
        
        $contentDisposition = $response->headers->get('content-disposition');
        $this->assertStringContainsString('attachment; filename=SAHAYU_Backup_', $contentDisposition);
        $this->assertStringEndsWith('.sql', $contentDisposition);

        // Capture response content stream
        ob_start();
        $response->sendContent();
        $output = ob_get_clean();

        $this->assertStringContainsString('-- SAHAYU Database Backup', $output);
        $this->assertStringContainsString('companies', $output);
        $this->assertStringContainsString('users', $output);
    }

    public function test_guest_accesses_root_gets_landing_page()
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Sistem Kasir & Analisis HPP Pintar untuk UMKM Kuliner Tangguh.', false);
        $response->assertSee('Mulai Kelola Bisnis Gratis');
        $response->assertSee('Kasir POS Kuliner Pintar');
        $response->assertSee('Dashboard Analitik & Ringkasan Performa', false);
        $response->assertSee('Manajemen Kasbon & Pengingat Jatuh Tempo', false);
    }

    public function test_authenticated_user_accesses_root_gets_dashboard()
    {
        $company = Company::create([
            'name' => 'SAHAYU Test Bakery',
        ]);

        $user = User::create([
            'name' => 'Test Admin',
            'email' => 'admin@sahayu.com',
            'password' => bcrypt('password123'),
            'company_id' => $company->id,
            'role' => 'admin',
        ]);

        $response = $this->actingAs($user)->get('/');

        $response->assertStatus(200);
        // The dashboard view DashboardUtama contains the shop header company name
        $response->assertSee('SAHAYU Test Bakery');
    }

    public function test_it_renders_debt_guided_tour_driver_assets_when_debts_exist()
    {
        $company = Company::create([
            'name' => 'SAHAYU Test Bakery',
        ]);

        $user = User::create([
            'name' => 'Test Admin',
            'email' => 'admin@sahayu.com',
            'password' => bcrypt('password123'),
            'company_id' => $company->id,
            'role' => 'admin',
        ]);

        $customer = \App\Models\Customer::create([
            'company_id' => $company->id,
            'name' => 'Pelanggan Setia',
            'phone' => '08123456789',
        ]);

        $debt = \App\Models\Debt::create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'total_amount' => 500000,
            'remaining_amount' => 500000,
            'due_date' => now()->addDays(5)->toDateString(),
            'status' => 'unpaid',
        ]);

        $response = $this->actingAs($user)->get(route('debts.index'));

        $response->assertStatus(200);
        // Renders the Driver.js CDN stylesheet and javascript iife
        $response->assertSee('https://cdn.jsdelivr.net/npm/driver.js@1.0.1/dist/driver.css', false);
        $response->assertSee('https://cdn.jsdelivr.net/npm/driver.js@1.0.1/dist/driver.js.iife.js', false);

        // Renders custom styles overrides
        $response->assertSee('/* Driver.js Popover Premium Customizations */', false);
        $response->assertSee('.driver-popover', false);

        // Renders the start tour button and key target selectors
        $response->assertSee('id="btn-start-tour"', false);
        $response->assertSee('💡 Panduan Kasir', false);
        $response->assertSee('id="tour-search-bar"', false);
        $response->assertSee('id="tour-customer-list"', false);
        $response->assertSee('id="tour-invoice-selector"', false);
        $response->assertSee('id="tour-quick-amounts"', false);
        $response->assertSee('id="tour-submit-button"', false);
    }

    public function test_it_does_not_render_guided_tour_button_when_no_debts_exist()
    {
        $company = Company::create([
            'name' => 'SAHAYU Test Bakery',
        ]);

        $user = User::create([
            'name' => 'Test Admin',
            'email' => 'admin@sahayu.com',
            'password' => bcrypt('password123'),
            'company_id' => $company->id,
            'role' => 'admin',
        ]);

        $response = $this->actingAs($user)->get(route('debts.index'));

        $response->assertStatus(200);
        // Should not see the tour button
        $response->assertDontSee('id="btn-start-tour"', false);
        $response->assertDontSee('💡 Panduan Kasir', false);
    }

    public function test_direct_add_stock_deducts_ingredients()
    {
        $company = Company::create([
            'name' => 'SAHAYU Test Bakery',
        ]);

        $user = User::create([
            'name' => 'Test Admin',
            'email' => 'admin@sahayu.com',
            'password' => bcrypt('password123'),
            'company_id' => $company->id,
            'role' => 'admin',
        ]);

        $material = \App\Models\Material::create([
            'company_id' => $company->id,
            'name' => 'Tepung Terigu',
            'stock' => 10.0,
            'minimum_stock' => 1.0,
            'unit' => 'Kg',
            'price' => 12000,
        ]);

        $product = \App\Models\Product::create([
            'company_id' => $company->id,
            'name' => 'Roti Tawar',
            'selling_price' => 15000,
            'stock' => 0,
            'minimum_stock' => 5,
        ]);

        // Attach material as ingredient with standard recipe quantity = 0.5 kg per product
        $product->ingredients()->attach($material->id, [
            'quantity' => 0.5,
            'company_id' => $company->id,
        ]);

        // Act: Add 4 units of product stock directly
        $response = $this->actingAs($user)->post(route('products.add-stock', $product), [
            'amount' => 4,
        ]);

        $response->assertRedirect(route('products.index'));
        $response->assertSessionHas('success');

        // Assert: Product stock increased to 4
        $this->assertEquals(4, $product->fresh()->stock);

        // Assert: Material stock decreased by 0.5 * 4 = 2.0. So remaining stock should be 10.0 - 2.0 = 8.0
        $this->assertEquals(8.0, (float) $material->fresh()->stock);

        // Assert: Stock movements were created
        $this->assertDatabaseHas('product_stock_movements', [
            'product_id' => $product->id,
            'type' => 'in',
            'quantity' => 4,
            'reference' => 'Tambah Stok Langsung',
        ]);

        $this->assertDatabaseHas('material_stock_movements', [
            'material_id' => $material->id,
            'type' => 'out',
            'quantity' => 2.0,
            'reference' => 'Tambah Stok Langsung',
        ]);
    }

    public function test_direct_add_stock_fails_if_insufficient_ingredients()
    {
        $company = Company::create([
            'name' => 'SAHAYU Test Bakery',
        ]);

        $user = User::create([
            'name' => 'Test Admin',
            'email' => 'admin@sahayu.com',
            'password' => bcrypt('password123'),
            'company_id' => $company->id,
            'role' => 'admin',
        ]);

        $material = \App\Models\Material::create([
            'company_id' => $company->id,
            'name' => 'Mentega Premium',
            'stock' => 1.0, // Only 1 kg in stock
            'minimum_stock' => 0.5,
            'unit' => 'Kg',
            'price' => 40000,
        ]);

        $product = \App\Models\Product::create([
            'company_id' => $company->id,
            'name' => 'Croissant Butter',
            'selling_price' => 25000,
            'stock' => 0,
            'minimum_stock' => 2,
        ]);

        // Standard recipe needs 0.3 kg of butter per croissant
        $product->ingredients()->attach($material->id, [
            'quantity' => 0.3,
            'company_id' => $company->id,
        ]);

        // Act: Try to add 4 croissants (needs 1.2 kg butter, but only 1.0 kg available)
        $response = $this->actingAs($user)->from(route('products.index'))->post(route('products.add-stock', $product), [
            'amount' => 4,
        ]);

        $response->assertRedirect(route('products.index'));
        $response->assertSessionHasErrors('amount');

        // Assert: Stocks remain unchanged
        $this->assertEquals(0, $product->fresh()->stock);
        $this->assertEquals(1.0, (float) $material->fresh()->stock);
    }

    public function test_bcg_matrix_ai_classification()
    {
        $company = Company::create([
            'name' => 'SAHAYU Test Bakery',
        ]);

        $user = User::create([
            'name' => 'Test Admin',
            'email' => 'admin@sahayu.com',
            'password' => bcrypt('password123'),
            'company_id' => $company->id,
            'role' => 'admin',
        ]);

        // Create 4 products
        $star = \App\Models\Product::create([
            'company_id' => $company->id,
            'name' => 'Roti Star',
            'selling_price' => 10000,
            'stock' => 50,
            'minimum_stock' => 5,
        ]);

        $cow = \App\Models\Product::create([
            'company_id' => $company->id,
            'name' => 'Roti Cash Cow',
            'selling_price' => 10000,
            'stock' => 50,
            'minimum_stock' => 5,
        ]);

        $question = \App\Models\Product::create([
            'company_id' => $company->id,
            'name' => 'Roti Question Mark',
            'selling_price' => 10000,
            'stock' => 50,
            'minimum_stock' => 5,
        ]);

        $dog = \App\Models\Product::create([
            'company_id' => $company->id,
            'name' => 'Roti Dog',
            'selling_price' => 10000,
            'stock' => 50,
            'minimum_stock' => 5,
        ]);

        // Create finished production batches to establish HPP (COGS)
        // Roti Star: COGS = 5000 -> Margin = 50%
        \App\Models\Production::create([
            'company_id' => $company->id,
            'product_id' => $star->id,
            'batch_code' => 'BATCH-001',
            'quantity' => 10,
            'good_quantity' => 10,
            'unit_hpp_snapshot' => 5000,
            'status' => 'done',
            'production_date' => now()->toDateString(),
        ]);

        // Roti Cash Cow: COGS = 8000 -> Margin = 20%
        \App\Models\Production::create([
            'company_id' => $company->id,
            'product_id' => $cow->id,
            'batch_code' => 'BATCH-002',
            'quantity' => 10,
            'good_quantity' => 10,
            'unit_hpp_snapshot' => 8000,
            'status' => 'done',
            'production_date' => now()->toDateString(),
        ]);

        // Roti Question Mark: COGS = 3000 -> Margin = 70%
        \App\Models\Production::create([
            'company_id' => $company->id,
            'product_id' => $question->id,
            'batch_code' => 'BATCH-003',
            'quantity' => 10,
            'good_quantity' => 10,
            'unit_hpp_snapshot' => 3000,
            'status' => 'done',
            'production_date' => now()->toDateString(),
        ]);

        // Roti Dog: COGS = 9000 -> Margin = 10%
        \App\Models\Production::create([
            'company_id' => $company->id,
            'product_id' => $dog->id,
            'batch_code' => 'BATCH-004',
            'quantity' => 10,
            'good_quantity' => 10,
            'unit_hpp_snapshot' => 9000,
            'status' => 'done',
            'production_date' => now()->toDateString(),
        ]);

        // Create sales in last 30 days
        $sale = \App\Models\Sale::create([
            'company_id' => $company->id,
            'total' => 360000,
            'payment_method' => 'cash',
            'status' => 'paid',
        ]);

        // Star sales = 15 (> average threshold 9.0)
        \App\Models\SaleItem::create([
            'company_id' => $company->id,
            'sale_id' => $sale->id,
            'product_id' => $star->id,
            'quantity' => 15,
            'price' => 10000,
        ]);

        // Cash Cow sales = 20 (> average threshold 9.0)
        \App\Models\SaleItem::create([
            'company_id' => $company->id,
            'sale_id' => $sale->id,
            'product_id' => $cow->id,
            'quantity' => 20,
            'price' => 10000,
        ]);

        // Question Mark sales = 1 (<= average threshold 9.0)
        \App\Models\SaleItem::create([
            'company_id' => $company->id,
            'sale_id' => $sale->id,
            'product_id' => $question->id,
            'quantity' => 1,
            'price' => 10000,
        ]);

        // Dog sales = 0 (<= average threshold 9.0)

        // Invoke AIService directly to test classifications
        $aiService = new \App\Services\AIService();
        $analysis = $aiService->getBcgMenuAnalysis($company->id);

        $this->assertCount(4, $analysis['products']);

        $starAnalysis = collect($analysis['products'])->firstWhere('product.id', $star->id);
        $cowAnalysis = collect($analysis['products'])->firstWhere('product.id', $cow->id);
        $questionAnalysis = collect($analysis['products'])->firstWhere('product.id', $question->id);
        $dogAnalysis = collect($analysis['products'])->firstWhere('product.id', $dog->id);

        $this->assertEquals('STAR', $starAnalysis['category']);
        $this->assertEquals('CASH COW', $cowAnalysis['category']);
        $this->assertEquals('QUESTION MARK', $questionAnalysis['category']);
        $this->assertEquals('DOG', $dogAnalysis['category']);

        // Test dashboard view response
        $response = $this->actingAs($user)->get('/');
        $response->assertStatus(200);
        $response->assertSee('SAHAYU AI Menu Insights');
        $response->assertSee('STAR');
        $response->assertSee('CASH COW');
        $response->assertSee('QUESTION MARK');
        $response->assertSee('DOG');
    }
}

