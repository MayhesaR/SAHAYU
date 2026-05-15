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

Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login')->middleware('guest');
Route::post('/login', [AuthController::class, 'login'])->middleware('guest');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

Route::middleware('auth')->group(function () {
    // Profil (Bisa diakses Admin & Staff)
    Route::get('/profil', [ProfileController::class, 'index'])->name('profile.index');
    Route::put('/profil', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profil/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    // SAHAYU AI Assistant (Semua user)
    Route::get('/ai-assistant', [AiReportController::class, 'index'])->name('ai.index');
    Route::post('/ai/analyze', [AiReportController::class, 'analyze'])->name('ai.analyze');
    Route::post('/ai/chatbot', [AiReportController::class, 'askChatbot'])->name('ai.chatbot');

    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Bahan Baku
    Route::get('/bahan-baku', [MaterialController::class, 'index'])->name('materials.index');
    Route::get('/bahan-baku/export-pdf', [MaterialController::class, 'exportPdf'])->name('materials.export-pdf');
    Route::get('/bahan-baku/export-sheets', [MaterialController::class, 'exportGoogleSheets'])->name('materials.export-sheets');
    Route::post('/bahan-baku/{material}/stok-masuk', [MaterialController::class, 'stockIn'])->name('materials.stock-in');
    Route::post('/bahan-baku/{material}/stok-keluar', [MaterialController::class, 'stockOut'])->name('materials.stock-out');

    // Produk Jadi (View untuk semua)
    Route::get('/produk', [ProductController::class, 'index'])->name('products.index');

    // Produksi
    Route::get('/produksi', [ProductionController::class, 'index'])->name('productions.index');
    Route::get('/produksi/export-pdf', [ProductionController::class, 'exportPdf'])->name('productions.export-pdf');
    Route::get('/produksi/export-sheets', [ProductionController::class, 'exportGoogleSheets'])->name('productions.export-sheets');

    // Penjualan
    Route::get('/penjualan', [SaleController::class, 'index'])->name('sales.index');
    Route::get('/penjualan/export-pdf', [SaleController::class, 'exportPdf'])->name('sales.export-pdf');
    Route::get('/penjualan/export-sheets', [SaleController::class, 'exportGoogleSheets'])->name('sales.export-sheets');
    Route::post('/penjualan', [SaleController::class, 'store'])->name('sales.store');

    // Laporan
    Route::get('/laporan', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/laporan/export-csv', [ReportController::class, 'exportExcel'])->name('reports.export-csv');
    Route::get('/laporan/export-pdf', [ReportController::class, 'exportPdf'])->name('reports.export-pdf');
    Route::get('/laporan/export-sheets', [ReportController::class, 'exportGoogleSheets'])->name('reports.export-sheets');

    // Export Bahan Baku (Hapus route lama yang ambigu)
    // Route::get('/bahan-baku-export', [MaterialController::class, 'exportPdf'])->name('materials.export');

    // Biaya Operasional (Overhead)
    Route::get('/overhead', [OverheadCostController::class, 'index'])->name('overhead.index');
    Route::post('/overhead', [OverheadCostController::class, 'store'])->name('overhead.store');
    Route::delete('/overhead/{overheadCost}', [OverheadCostController::class, 'destroy'])->name('overhead.destroy');

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

        // Produk Jadi
        Route::post('/products', [ProductController::class, 'store'])->name('products.store');
        Route::put('/products/{product}', [ProductController::class, 'update'])->name('products.update');
        Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');

        // Produksi
        Route::post('/produksi', [ProductionController::class, 'store'])->name('productions.store');
        Route::patch('/produksi', [ProductionController::class, 'updateStatusFromIndex'])->name('productions.update-status-fallback');
        Route::patch('/produksi/{production}/status', [ProductionController::class, 'updateStatus'])->name('productions.update-status');
        Route::delete('/produksi/{production}', [ProductionController::class, 'destroy'])->name('productions.destroy');

        // Penjualan
        Route::delete('/penjualan/{sale}', [SaleController::class, 'destroy'])->name('sales.destroy');

        // Manajemen Akun
        Route::get('/akun', [AccountController::class, 'index'])->name('accounts.index');
        Route::post('/akun', [AccountController::class, 'store'])->name('accounts.store');
        Route::put('/akun/{user}', [AccountController::class, 'update'])->name('accounts.update');
        Route::delete('/akun/{user}', [AccountController::class, 'destroy'])->name('accounts.destroy');
    });
});
