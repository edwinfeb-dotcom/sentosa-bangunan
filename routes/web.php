<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Master\CategoryController;
use App\Http\Controllers\Master\ProductController;
use App\Http\Controllers\Master\SupplierController;
use App\Http\Controllers\Master\CustomerController;
use App\Http\Controllers\Inventory\PurchaseOrderController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/login');
});

Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    
    Route::get('/dashboard', [\App\Http\Controllers\ControlCenterController::class, 'index'])->name('dashboard');
    Route::get('daily-reports/export', [\App\Http\Controllers\DailyReportController::class, 'export'])->name('daily-reports.export');

    // --- MASTER DATA ---
    Route::prefix('master')->name('master.')->group(function () {
        Route::resource('categories', CategoryController::class);
        Route::post('products/import', [ProductController::class, 'import'])->name('products.import');
        Route::get('products/barcode/print', [\App\Http\Controllers\Master\ProductController::class, 'barcodeIndex'])->name('products.barcode');
        Route::resource('products', ProductController::class);
        Route::resource('suppliers', SupplierController::class);
        Route::resource('customers', CustomerController::class);
        Route::post('customers/{customer}/deposit', [\App\Http\Controllers\Master\CustomerController::class, 'addDeposit'])->name('customers.deposit');
    });

    // --- INVENTORY ---
    Route::prefix('inventory')->name('inventory.')->group(function () {
        Route::resource('po', PurchaseOrderController::class)->except(['show', 'edit', 'update', 'destroy']);
        
        // INI ROUTE YANG TADI HILANG
        Route::post('po/{po}/receive', [PurchaseOrderController::class, 'receive'])->name('po.receive');
    });

        // --- POS & KASIR ---
    Route::prefix('kasir')->name('pos.')->group(function () {
        Route::get('sesi', [\App\Http\Controllers\Pos\CashSessionController::class, 'index'])->name('session.index');
        Route::post('sesi/buka', [\App\Http\Controllers\Pos\CashSessionController::class, 'open'])->name('session.open');
        // Route pos.index akan kita buat di langkah berikutnya
        Route::get('/', [\App\Http\Controllers\Pos\PosController::class, 'index'])->name('index');
        Route::post('/checkout', [\App\Http\Controllers\Pos\PosController::class, 'store'])->name('store');
        Route::get('/struk/{sale}', [\App\Http\Controllers\Pos\PosController::class, 'receipt'])->name('receipt');
        Route::post('/tutup-kasir', [\App\Http\Controllers\Pos\CashSessionController::class, 'close'])->name('session.close');
    });

        // --- FINANCE ---
    Route::prefix('finance')->name('finance.')->group(function () {
        Route::resource('cash-ledger', \App\Http\Controllers\Finance\CashLedgerController::class)->except(['show', 'edit', 'update', 'destroy']);
    });

    Route::resource('daily-reports', \App\Http\Controllers\DailyReportController::class)->only(['index', 'create', 'store']);
    Route::post('daily-reports/{dailyReport}/verify', [\App\Http\Controllers\DailyReportController::class, 'verify'])->name('daily-reports.verify');

        // --- HR (SDM) ---
    Route::prefix('hr')->name('hr.')->group(function () {
        Route::get('/attendance', [\App\Http\Controllers\Hr\AttendanceController::class, 'index'])->name('attendance.index');
        Route::post('/attendance/clock-in', [\App\Http\Controllers\Hr\AttendanceController::class, 'clockIn'])->name('attendance.clock-in');
        Route::post('/attendance/clock-out', [\App\Http\Controllers\Hr\AttendanceController::class, 'clockOut'])->name('attendance.clock-out');
        Route::get('/payroll', [\App\Http\Controllers\Hr\PayrollController::class, 'index'])->name('payroll.index');
        Route::post('/payroll/generate', [\App\Http\Controllers\Hr\PayrollController::class, 'generate'])->name('payroll.generate');
        Route::get('/payroll/{payroll}/slip', [\App\Http\Controllers\Hr\PayrollController::class, 'slip'])->name('payroll.slip');
        Route::resource('/employees', \App\Http\Controllers\Hr\EmployeeController::class);
    });

        // --- FLEET (ARMADA) ---
    Route::prefix('fleet')->name('fleet.')->group(function () {
        Route::resource('vehicles', \App\Http\Controllers\Fleet\VehicleController::class);
        Route::resource('jobs', \App\Http\Controllers\Fleet\TransportJobController::class)->except(['show', 'edit', 'update', 'destroy']);
        Route::post('jobs/{job}/complete', [\App\Http\Controllers\Fleet\TransportJobController::class, 'complete'])->name('jobs.complete');
    });

    // --- PROJECT ---
    Route::prefix('project')->name('project.')->group(function () {
        Route::resource('projects', \App\Http\Controllers\Project\ProjectController::class);
    });

        // --- SETTING & AKSES ---
    Route::prefix('setting')->name('setting.')->group(function () {
        Route::get('/access', [\App\Http\Controllers\Setting\AccessController::class, 'index'])->name('access.index');
        Route::put('/access/{user}', [\App\Http\Controllers\Setting\AccessController::class, 'update'])->name('access.update');
        Route::get('/backup', [\App\Http\Controllers\Setting\BackupController::class, 'index'])->name('backup.index');
        Route::get('/backup/export-reports', [\App\Http\Controllers\Setting\BackupController::class, 'exportReports'])->name('backup.export-reports');
    });

});