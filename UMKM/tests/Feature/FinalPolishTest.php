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
}

