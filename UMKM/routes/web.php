<?php

use App\Http\Controllers\HppController;
use App\Http\Controllers\OverheadCostController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductionController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AiReportController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\TransactionHistoryController;
use App\Http\Controllers\DebtController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\SettingsController;

Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login')->middleware('guest');
Route::post('/login', [AuthController::class, 'login'])->middleware('guest');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register')->middleware('guest');
Route::post('/register', [RegisterController::class, 'register'])->middleware('guest');

// Temporary setup route for Railway (to migrate, seed, and link storage via browser)
Route::get('/run-setup', function () {
    try {
        $storageLink = \Illuminate\Support\Facades\Artisan::call('storage:link', ['--force' => true]);

        return response()->json([
            'status' => 'success',
            'message' => 'Setup completed successfully!',
            'output' => \Illuminate\Support\Facades\Artisan::output(),
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage()
        ], 500);
    }
});

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

// Main Authenticated Workspace Routes
Route::middleware('auth')->group(function () {
    Route::get('/dashboard/export', [DashboardController::class, 'exportExcel'])->name('dashboard.export');

    // Profil Saya
    Route::get('/profil', [ProfileController::class, 'index'])->name('profile.index');
    Route::put('/profil', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profil/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    // SAHAYU AI Assistant
    Route::get('/assistant', [AiReportController::class, 'index'])->name('ai.index');
    Route::post('/assistant/analyze', [AiReportController::class, 'analyze'])->name('ai.analyze');
    Route::post('/assistant/chat', [AiReportController::class, 'askChatbot'])->name('ai.chatbot');

    // Riwayat Transaksi & Catatan Utang
    Route::get('/history-transaksi', [TransactionHistoryController::class, 'index'])->name('history.index');
    Route::get('/history-transaksi/export', [TransactionHistoryController::class, 'export'])->name('history.export');
    Route::get('/catatan-utang', [DebtController::class, 'index'])->name('debts.index');
    Route::post('/catatan-utang/{debt}/bayar', [DebtController::class, 'payInstallment'])->name('debts.pay');
    Route::post('/catatan-utang/pelanggan/{customer}/bayar-banyak', [DebtController::class, 'payMultipleInstallments'])->name('debts.pay-multiple');

    // Customer CRM Routes
    Route::get('/pelanggan', [CustomerController::class, 'index'])->name('customers.index');
    Route::get('/pelanggan/export', [CustomerController::class, 'exportExcel'])->name('customers.export');
    Route::post('/pelanggan', [CustomerController::class, 'store'])->name('customers.store');
    Route::put('/pelanggan/{customer}', [CustomerController::class, 'update'])->name('customers.update');

    // Bahan Baku
    Route::get('/bahan-baku', [MaterialController::class, 'index'])->name('materials.index');
    Route::get('/bahan-baku/export', [MaterialController::class, 'exportExcel'])->name('materials.export');
    Route::get('/bahan-baku/export-pdf', [MaterialController::class, 'exportPdf'])->name('materials.export-pdf');
    Route::get('/bahan-baku/export-sheets', [MaterialController::class, 'exportGoogleSheets'])->name('materials.export-sheets');
    Route::post('/bahan-baku/kategori', [MaterialController::class, 'storeCategory'])->name('materials.categories.store');
    Route::post('/bahan-baku/{material}/stok-masuk', [MaterialController::class, 'stockIn'])->name('materials.stock-in');
    Route::post('/bahan-baku/{material}/stok-keluar', [MaterialController::class, 'stockOut'])->name('materials.stock-out');

    // Produk Jadi (View untuk semua)
    Route::get('/produk', [ProductController::class, 'index'])->name('products.index');

    // Produksi
    Route::get('/produksi', [ProductionController::class, 'index'])->name('productions.index');
    Route::get('/produksi/resep/{product}', [ProductionController::class, 'getIngredients'])->name('productions.ingredients');
    Route::get('/produksi/export', [ProductionController::class, 'exportExcel'])->name('productions.export');
    Route::get('/produksi/export-pdf', [ProductionController::class, 'exportPdf'])->name('productions.export-pdf');
    Route::get('/produksi/export-sheets', [ProductionController::class, 'exportGoogleSheets'])->name('productions.export-sheets');

    // Penjualan
    Route::get('/penjualan', [SaleController::class, 'index'])->name('sales.index');
    Route::get('/penjualan/export-pdf', [SaleController::class, 'exportPdf'])->name('sales.export-pdf');
    Route::get('/penjualan/export-sheets', [SaleController::class, 'exportGoogleSheets'])->name('sales.export-sheets');
    Route::post('/penjualan', [SaleController::class, 'store'])->name('sales.store');
    Route::get('/penjualan/{sale}/struk', [SaleController::class, 'showReceipt'])->name('sales.receipt');

    // Laporan
    Route::get('/laporan', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/laporan/export-csv', [ReportController::class, 'exportExcel'])->name('reports.export-csv');
    Route::get('/laporan/export-pdf', [ReportController::class, 'exportPdf'])->name('reports.export-pdf');
    Route::get('/laporan/export-sheets', [ReportController::class, 'exportGoogleSheets'])->name('reports.export-sheets');

    // Biaya Operasional (Overhead)
    Route::get('/overhead', [OverheadCostController::class, 'index'])->name('overhead.index');
    Route::get('/overhead/export', [OverheadCostController::class, 'exportExcel'])->name('overhead.export');
    Route::post('/overhead', [OverheadCostController::class, 'store'])->name('overhead.store');
    Route::delete('/overhead/{overheadCost}', [OverheadCostController::class, 'destroy'])->name('overhead.destroy');

    // Pengeluaran Operasional (Petty Cash/Kas Keluar)
    Route::get('/pengeluaran', [ExpenseController::class, 'index'])->name('expenses.index');
    Route::get('/pengeluaran/export', [ExpenseController::class, 'exportExcel'])->name('expenses.export');
    Route::post('/pengeluaran', [ExpenseController::class, 'store'])->name('expenses.store');
    Route::delete('/pengeluaran/{expense}', [ExpenseController::class, 'destroy'])->name('expenses.destroy');



    // Khusus Admin
    Route::middleware('role:admin')->group(function () {
        // HPP Otomatis
        Route::get('/hpp-otomatis', [HppController::class, 'index'])->name('hpp.index');

        // Bahan Baku
        Route::post('/bahan-baku', [MaterialController::class, 'store'])->name('materials.store');
        Route::put('/bahan-baku/{material}', [MaterialController::class, 'update'])->name('materials.update');
        Route::post('/bahan-baku/{material}/penyesuaian', [MaterialController::class, 'adjustStock'])->name('materials.adjust-stock');
        Route::patch('/bahan-baku/{material}/minimum-stok', [MaterialController::class, 'updateMinimumStock'])->name('materials.update-minimum-stock');
        Route::delete('/bahan-baku/{material}', [MaterialController::class, 'destroy'])->name('materials.destroy');

        // Produk Jadi (Admin CRUD)
        Route::post('/products', [ProductController::class, 'store'])->name('products.store');
        Route::put('/products/{product}', [ProductController::class, 'update'])->name('products.update');
        Route::post('/products/{product}/add-stock', [ProductController::class, 'addStock'])->name('products.add-stock');
        Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');

        // Customer Delete (Admin Only)
        Route::delete('/pelanggan/{customer}', [CustomerController::class, 'destroy'])->name('customers.destroy');

        // Produksi
        Route::post('/produksi', [ProductionController::class, 'store'])->name('productions.store');
        Route::patch('/produksi', [ProductionController::class, 'updateStatusFromIndex'])->name('productions.update-status-fallback');
        Route::patch('/produksi/{production}/status', [ProductionController::class, 'updateStatus'])->name('productions.update-status');
        Route::delete('/produksi/{production}', [ProductionController::class, 'destroy'])->name('productions.destroy');

        // Penjualan Destroy (Admin Only)
        Route::delete('/penjualan/{sale}', [SaleController::class, 'destroy'])->name('sales.destroy');

        // Manajemen Akun
        Route::get('/akun', [AccountController::class, 'index'])->name('accounts.index');
        Route::post('/akun', [AccountController::class, 'store'])->name('accounts.store');
        Route::put('/akun/{user}', [AccountController::class, 'update'])->name('accounts.update');
        Route::delete('/akun/{user}', [AccountController::class, 'destroy'])->name('accounts.destroy');
    });

    // Settings
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::post('/settings/company', [SettingsController::class, 'updateCompany'])->name('settings.company');
    Route::post('/settings/password', [SettingsController::class, 'updatePassword'])->name('settings.password');
    Route::post('/settings/printer', [SettingsController::class, 'updatePrinter'])->name('settings.printer');
    Route::get('/settings/backup', [SettingsController::class, 'downloadBackup'])->name('settings.backup');
});
