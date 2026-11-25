<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\ControlCenterController;
use App\Http\Controllers\CustomerAuthController;
use App\Http\Controllers\DailyReportController;
use App\Http\Controllers\Finance\CashFlowController;
use App\Http\Controllers\Finance\CashLedgerController;
use App\Http\Controllers\Finance\WalletController;
use App\Http\Controllers\Fleet\AssetController;
use App\Http\Controllers\Fleet\TransportJobController;
use App\Http\Controllers\Fleet\VehicleController;
use App\Http\Controllers\Hr\AttendanceController;
use App\Http\Controllers\Hr\EmployeeController;
use App\Http\Controllers\Hr\PayrollController;
use App\Http\Controllers\Inventory\PurchaseOrderController;
use App\Http\Controllers\Inventory\PurchaseReturnController;
use App\Http\Controllers\Inventory\StockMutationController;
use App\Http\Controllers\Inventory\SupplierInvoiceController;
use App\Http\Controllers\Master\CategoryController;
use App\Http\Controllers\Master\CustomerController;
use App\Http\Controllers\Master\ProductController;
use App\Http\Controllers\Master\SupplierController;
use App\Http\Controllers\Pos\CashSessionController;
use App\Http\Controllers\Pos\PosController;
use App\Http\Controllers\Project\ProjectController;
use App\Http\Controllers\PublicCatalogController;
use App\Http\Controllers\PublicOrderController;
use App\Http\Controllers\Report\SalesReportController;
use App\Http\Controllers\Setting\AccessController;
use App\Http\Controllers\Setting\BackupController;
use App\Http\Controllers\Setting\ChecklistTemplateController;
use App\Http\Controllers\Setting\SalesTargetController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return auth()->user()->role === 'pelanggan'
            ? redirect()->route('catalog.index')
            : redirect()->route('dashboard');
    }

    return redirect()->route('login');
});

// --- AUTH ---
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// --- MENU PUBLIK (KATALOG, CHECKOUT, TRACKING, AKUN PELANGGAN) ---
Route::get('/katalog', [PublicCatalogController::class, 'index'])->name('catalog.index');
Route::post('/katalog/cart-sync', [PublicCatalogController::class, 'syncCart'])->name('catalog.cart-sync');
Route::get('/katalog/checkout', [PublicCatalogController::class, 'checkoutForm'])->name('catalog.checkout');
Route::post('/katalog/checkout', [PublicCatalogController::class, 'storeOrder'])->name('catalog.store-order');
Route::get('/katalog/sukses/{order_code}', [PublicCatalogController::class, 'success'])->name('catalog.success');
Route::get('/lacak-pesanan', [PublicCatalogController::class, 'track'])->name('catalog.track');

Route::get('/masuk', [CustomerAuthController::class, 'showLogin'])->name('customer.login');
Route::post('/masuk', [CustomerAuthController::class, 'login']);
Route::get('/daftar', [CustomerAuthController::class, 'showRegister'])->name('customer.register');
Route::post('/daftar', [CustomerAuthController::class, 'register']);
Route::middleware('auth')->group(function () {
    Route::get('/akun', [CustomerAuthController::class, 'account'])->name('catalog.account');
    Route::put('/akun', [CustomerAuthController::class, 'updateProfile'])->name('catalog.account.update');
});

// --- MENU UTAMA (STAFF TOKO: OWNER / ADMIN / KASIR / GUDANG / DRIVER / KARYAWAN) ---
Route::middleware(['auth', 'role:owner,admin,kasir,gudang,driver,karyawan'])->group(function () {

    // Dashboard (otomatis menampilkan Beranda Kasir khusus jika role === 'kasir')
    Route::get('/dashboard', [ControlCenterController::class, 'index'])->name('dashboard');
    Route::get('/qr-katalog', [PublicCatalogController::class, 'qrPoster'])->name('catalog.qr');

    // --- LAPORAN HARIAN & PENJUALAN (Dapat diakses Kasir & Admin/Owner) ---
    Route::get('daily-reports/export', [DailyReportController::class, 'export'])->name('daily-reports.export');
    Route::resource('daily-reports', DailyReportController::class)->only(['index', 'create', 'store']);
    Route::post('daily-reports/{dailyReport}/verify', [DailyReportController::class, 'verify'])
        ->middleware('role:owner,admin')
        ->name('daily-reports.verify');

    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('sales', [SalesReportController::class, 'index'])->name('sales.index');
    });

    // --- MASTER DATA OPERASIONAL KASIR (Kategori, Produk, Pelanggan) ---
    Route::prefix('master')->name('master.')->group(function () {
        Route::resource('categories', CategoryController::class)->except(['show']);
        Route::get('products/template/download', [ProductController::class, 'downloadTemplate'])->name('products.template');
        Route::get('products/export/excel', [ProductController::class, 'export'])->name('products.export');
        Route::post('products/import', [ProductController::class, 'import'])->name('products.import');
        Route::get('products/images/template', [ProductController::class, 'downloadImageTemplate'])->name('products.images.template');
        Route::post('products/images/import', [ProductController::class, 'importImages'])->name('products.images.import');
        Route::post('products/images/batch', [ProductController::class, 'importImagesBatch'])->name('products.images.batch');
        Route::get('products/barcode/print', [ProductController::class, 'barcodeIndex'])->name('products.barcode');
        Route::resource('products', ProductController::class)->except(['show']);
        Route::resource('customers', CustomerController::class);
        Route::post('customers/{customer}/deposit', [CustomerController::class, 'addDeposit'])->name('customers.deposit');

        // Master Supplier khusus Owner / Admin / Gudang
        Route::resource('suppliers', SupplierController::class)->except(['show'])->middleware('role:owner,admin,gudang');
    });

    // --- INVENTORY & PEMBELIAN (PO, Nota Supplier, Retur Supplier) ---
    Route::prefix('inventory')->name('inventory.')->group(function () {
        Route::resource('po', PurchaseOrderController::class)->except(['show', 'edit', 'update', 'destroy']);
        Route::post('po/{po}/receive', [PurchaseOrderController::class, 'receive'])->name('po.receive');
        Route::get('/supplier-invoices', [SupplierInvoiceController::class, 'index'])->name('invoices.index');
        Route::post('/supplier-invoices/{invoice}/pay', [SupplierInvoiceController::class, 'pay'])->name('invoices.pay');
        Route::resource('returns', PurchaseReturnController::class)->only(['index', 'create', 'store']);
        Route::get('/stock-mutations', [StockMutationController::class, 'index'])->name('stock.index');
        Route::post('/stock-mutations/opname', [StockMutationController::class, 'opname'])->name('stock.opname');
    });

    // --- POS & KASIR (Khusus Owner / Admin / Kasir) ---
    Route::middleware('role:owner,admin,kasir')->prefix('kasir')->name('pos.')->group(function () {
        Route::get('sesi', [CashSessionController::class, 'index'])->name('session.index');
        Route::post('sesi/buka', [CashSessionController::class, 'open'])->name('session.open');
        Route::get('/', [PosController::class, 'index'])->name('index');
        Route::post('/checkout', [PosController::class, 'store'])->name('store');
        Route::get('/struk/{sale}', [PosController::class, 'receipt'])->name('receipt');
        Route::post('/tutup-kasir', [CashSessionController::class, 'close'])->name('session.close');
    });

    // --- PESANAN ONLINE ---
    Route::get('/public-orders', [PublicOrderController::class, 'index'])->name('public-orders.index');
    Route::post('/public-orders/{order}/process', [PublicOrderController::class, 'process'])->name('public-orders.process');
    Route::post('/public-orders/{order}/status', [PublicOrderController::class, 'updateStatus'])->name('public-orders.status');

    // --- FINANCE ---
    Route::prefix('finance')->name('finance.')->group(function () {
        // Buku Kas bisa diakses kasir untuk kas kecil shift
        Route::resource('cash-ledger', CashLedgerController::class)->except(['show', 'edit', 'update', 'destroy']);

        // Dompet Multi-Rekening & Arus Kas khusus Owner / Admin
        Route::middleware('role:owner,admin')->group(function () {
            Route::get('wallets', [WalletController::class, 'index'])->name('wallets.index');
            Route::post('wallets', [WalletController::class, 'store'])->name('wallets.store');
            Route::put('wallets/{wallet}', [WalletController::class, 'update'])->name('wallets.update');
            Route::post('wallets/transfer', [WalletController::class, 'transfer'])->name('wallets.transfer');
            Route::get('cash-flow', [CashFlowController::class, 'index'])->name('cash-flow.index');
        });
    });

    // --- HR (SDM & PAYROLL) ---
    Route::prefix('hr')->name('hr.')->group(function () {
        // Absensi pribadi & Scan QR bisa diakses semua staff termasuk kasir & karyawan
        Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
        Route::post('/attendance/clock-in', [AttendanceController::class, 'clockIn'])->name('attendance.clock-in');
        Route::post('/attendance/clock-out', [AttendanceController::class, 'clockOut'])->name('attendance.clock-out');
        Route::post('/attendance/scan-qr', [AttendanceController::class, 'scanQr'])->name('attendance.scan-qr');

        // Rekap Absensi, Kiosk QR, Payroll & Data Karyawan khusus Owner / Admin
        Route::middleware('role:owner,admin')->group(function () {
            Route::get('/attendance/qr-kiosk', [AttendanceController::class, 'qrKiosk'])->name('attendance.qr-kiosk');
            Route::post('/attendance/qr-refresh', [AttendanceController::class, 'refreshQr'])->name('attendance.qr-refresh');
            Route::post('/attendance/mark-status', [AttendanceController::class, 'markStatus'])->name('attendance.mark-status');
            Route::post('/attendance/sync-alpha', [AttendanceController::class, 'syncAlpha'])->name('attendance.sync-alpha');
            Route::get('/attendance/export-monthly', [AttendanceController::class, 'exportMonthly'])->name('attendance.export-monthly');

            Route::get('/payroll', [PayrollController::class, 'index'])->name('payroll.index');
            Route::post('/payroll/generate', [PayrollController::class, 'generate'])->name('payroll.generate');
            Route::put('/payroll/salary-config/{employee}', [PayrollController::class, 'updateSalaryConfig'])->name('payroll.salary-config');
            Route::put('/payroll/{payroll}/bonus', [PayrollController::class, 'updateBonus'])->name('payroll.bonus');
            Route::post('/payroll/{payroll}/pay', [PayrollController::class, 'markPaid'])->name('payroll.pay');
            Route::get('/payroll/{payroll}/slip', [PayrollController::class, 'slip'])->name('payroll.slip');
            Route::resource('/employees', EmployeeController::class)->except(['show']);
        });
    });

    // --- FLEET & PROJECT (Khusus Owner / Admin / Gudang / Driver) ---
    Route::middleware('role:owner,admin,gudang,driver')->group(function () {
        Route::prefix('fleet')->name('fleet.')->group(function () {
            Route::resource('vehicles', VehicleController::class)->except(['show']);
            Route::resource('jobs', TransportJobController::class)->except(['show', 'edit', 'update', 'destroy']);
            Route::post('jobs/{job}/complete', [TransportJobController::class, 'complete'])->name('jobs.complete');
            Route::resource('assets', AssetController::class)->except(['show']);
        });

        Route::prefix('project')->name('project.')->group(function () {
            Route::resource('projects', ProjectController::class);
            Route::post('projects/{project}/materials', [ProjectController::class, 'storeMaterial'])->name('projects.materials.store');
            Route::post('projects/{project}/materials/return', [ProjectController::class, 'returnMaterial'])->name('projects.materials.return');
        });
    });

    // --- SETTING & AKSES (Khusus Owner / Admin) ---
    Route::middleware('role:owner,admin')->prefix('setting')->name('setting.')->group(function () {
        Route::get('/targets', [SalesTargetController::class, 'index'])->name('targets.index');
        Route::post('/targets', [SalesTargetController::class, 'store'])->name('targets.store');
        Route::delete('/targets/{target}', [SalesTargetController::class, 'destroy'])->name('targets.destroy');

        Route::get('/checklists', [ChecklistTemplateController::class, 'index'])->name('checklists.index');
        Route::post('/checklists', [ChecklistTemplateController::class, 'store'])->name('checklists.store');
        Route::put('/checklists/{checklist}', [ChecklistTemplateController::class, 'update'])->name('checklists.update');
        Route::delete('/checklists/{checklist}', [ChecklistTemplateController::class, 'destroy'])->name('checklists.destroy');

        Route::get('/access', [AccessController::class, 'index'])->name('access.index')->middleware('role:owner');
        Route::post('/access', [AccessController::class, 'store'])->name('access.store')->middleware('role:owner');
        Route::put('/access/{user}', [AccessController::class, 'update'])->name('access.update')->middleware('role:owner');

        Route::get('/backup', [BackupController::class, 'index'])->name('backup.index');
        Route::get('/backup/export-reports', [BackupController::class, 'exportReports'])->name('backup.export-reports');
        Route::get('/backup/download-sql', [BackupController::class, 'downloadSql'])->name('backup.download-sql');
    });
});
