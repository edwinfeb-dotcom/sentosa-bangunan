<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\CashLedger;
use App\Models\CashSession;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Payroll;
use App\Models\Product;
use App\Models\Project;
use App\Models\PublicOrder;
use App\Models\SalesTarget;
use App\Models\StockLedger;
use App\Models\Supplier;
use App\Models\SupplierInvoice;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class SentosaAppTest extends TestCase
{
    use DatabaseTransactions;

    private function createStaffUser(string $role = 'owner'): User
    {
        return User::create([
            'name' => 'Test ' . ucfirst($role),
            'email' => $role . '_' . Str::random(6) . '@sentosa.test',
            'password' => Hash::make('password'),
            'role' => $role,
            'daily_salary' => 100000,
        ]);
    }

    public function test_public_catalog_checkout_tracking_and_customer_account(): void
    {
        $category = Category::create(['name' => 'Semen Test', 'is_active' => true]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Semen Test 50Kg',
            'unit' => 'Sak',
            'purchase_price' => 50000,
            'selling_price' => 65000,
            'stock_minimum' => 5,
            'is_active' => true,
        ]);

        $owner = $this->createStaffUser('owner');
        StockLedger::create([
            'product_id' => $product->id,
            'user_id' => $owner->id,
            'type' => 'in',
            'qty' => 20,
            'stock_before' => 0,
            'stock_after' => 20,
            'description' => 'Initial Stock',
        ]);

        // Public catalog search & filter
        $this->get(route('catalog.index', ['search' => 'Semen Test', 'sort' => 'price_asc']))
            ->assertStatus(200)
            ->assertSee('Semen Test 50Kg');

        // Customer registration
        $this->post(route('customer.register'), [
            'name' => 'Pembeli Baru',
            'email' => 'pembeli_' . Str::random(5) . '@mail.test',
            'phone' => '081234567890',
            'address' => 'Jl. Merdeka No. 1',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ])->assertRedirect(route('catalog.account'));

        // Customer cannot access ERP dashboard
        $this->get(route('dashboard'))->assertStatus(403);

        // Cart sync to DB
        $this->postJson(route('catalog.cart-sync'), [
            'cart' => [['id' => $product->id, 'qty' => 2]],
        ])->assertOk()->assertJson(['status' => 'synced']);

        // Store order with ongkir (2 * 65.000 = 130.000 < 1.000.000 => + 25.000)
        $response = $this->post(route('catalog.store-order'), [
            'customer_name' => 'Pembeli Baru',
            'phone' => '081234567890',
            'shipping_method' => 'antar',
            'address' => 'Jl. Merdeka No. 1',
            'payment_method' => 'transfer',
            'cart_data' => json_encode([['id' => $product->id, 'qty' => 2]]),
        ]);

        $order = PublicOrder::latest('id')->first();
        $this->assertNotNull($order);
        $this->assertEquals(25000.0, (float) $order->shipping_cost);
        $this->assertEquals(155000.0, (float) $order->total_amount);
        $response->assertRedirect(route('catalog.success', $order->order_code));

        // Track order publicly
        $this->get(route('catalog.track', ['order_code' => $order->order_code]))
            ->assertStatus(200)
            ->assertSee($order->order_code);
    }

    public function test_staff_dashboard_pos_and_all_new_erp_modules(): void
    {
        $owner = $this->createStaffUser('owner');
        $this->actingAs($owner);

        // Setup product & stock
        $category = Category::create(['name' => 'Material Uji', 'is_active' => true]);
        $product = Product::create([
            'category_id' => $category->id,
            'barcode' => 'BRG-' . Str::random(6),
            'name' => 'Cat Tembok 5Kg',
            'unit' => 'Pail',
            'purchase_price' => 100000,
            'selling_price' => 130000,
            'stock_minimum' => 2,
            'is_active' => true,
        ]);
        StockLedger::create([
            'product_id' => $product->id,
            'user_id' => $owner->id,
            'type' => 'in',
            'qty' => 50,
            'stock_before' => 0,
            'stock_after' => 50,
            'description' => 'Initial Stock',
        ]);

        // Sales Target & Dashboard
        $this->post(route('setting.targets.store'), [
            'month' => now('Asia/Pontianak')->month,
            'year' => now('Asia/Pontianak')->year,
            'target_amount' => 10000000,
        ])->assertRedirect();
        $this->assertDatabaseHas('sales_targets', ['target_amount' => 10000000]);

        $this->get(route('dashboard'))->assertStatus(200)->assertSee('Progress Target Penjualan');

        // Open POS & Checkout
        $this->post(route('pos.session.open'), ['opening_balance' => 100000])->assertRedirect(route('pos.index'));
        $this->get(route('pos.index'))->assertStatus(200);

        $this->postJson(route('pos.store'), [
            'idempotency_key' => (string) Str::uuid(),
            'items' => [['product_id' => $product->id, 'qty' => 3]],
            'payment_method' => 'cash',
            'paid_amount' => 400000,
        ])->assertOk()->assertJson(['status' => 'success']);

        $this->assertEquals(47, $product->fresh()->current_stock);

        // Sales Report
        $this->get(route('reports.sales.index'))->assertStatus(200)->assertSee('Cat Tembok 5Kg');

        // Supplier, Invoice, and Purchase Return (cut_debt)
        $supplier = Supplier::create(['name' => 'PT Pemasok Uji', 'is_active' => true]);
        SupplierInvoice::create([
            'supplier_id' => $supplier->id,
            'invoice_number' => 'INV-SUP-001',
            'total_amount' => 500000,
            'paid_amount' => 0,
            'status' => 'unpaid',
        ]);

        $this->post(route('inventory.returns.store'), [
            'return_number' => 'RET-TEST-' . Str::random(4),
            'supplier_id' => $supplier->id,
            'return_date' => date('Y-m-d'),
            'resolution' => 'cut_debt',
            'items' => [
                ['product_id' => $product->id, 'qty' => 2, 'price' => 100000, 'reason' => 'Kaleng penyok'],
            ],
        ])->assertRedirect(route('inventory.returns.index'));

        $this->assertEquals(45, $product->fresh()->current_stock);
        $this->assertDatabaseHas('supplier_invoices', ['invoice_number' => 'INV-SUP-001', 'paid_amount' => 200000, 'status' => 'partial']);

        // Wallets & Transfer & Cash Flow
        $wallets = Wallet::take(2)->get();
        $this->assertCount(2, $wallets);
        $this->post(route('finance.wallets.transfer'), [
            'from_wallet_id' => $wallets[0]->id,
            'to_wallet_id' => $wallets[1]->id,
            'amount' => 50000,
            'date' => date('Y-m-d'),
        ])->assertRedirect();
        $this->get(route('finance.wallets.index'))->assertStatus(200);
        $this->get(route('finance.cash-flow.index'))->assertStatus(200);

        // Payroll Bonus & Mark Paid
        $emp = $this->createStaffUser('karyawan');
        $payroll = Payroll::create([
            'user_id' => $emp->id,
            'month' => 11,
            'year' => 2026,
            'basic_salary' => 2000000,
            'allowance' => 0,
            'deduction' => 100000,
            'net_salary' => 1900000,
            'status' => 'draft',
        ]);
        $this->put(route('hr.payroll.bonus', $payroll->id), [
            'allowance' => 250000,
            'bonus_notes' => 'Bonus rajin',
        ])->assertRedirect();
        $this->assertEquals(2150000, (float) $payroll->fresh()->net_salary);

        $this->post(route('hr.payroll.pay', $payroll->id), ['wallet_id' => $wallets[0]->id])->assertRedirect();
        $this->assertEquals('paid', $payroll->fresh()->status);

        // Asset Register & Depreciation
        $this->post(route('fleet.assets.store'), [
            'code' => 'AST-TEST-' . Str::random(4),
            'name' => 'Komputer Kasir Utama',
            'category' => 'IT',
            'purchase_date' => date('Y-m-d'),
            'purchase_price' => 6000000,
            'useful_life_years' => 5,
            'residual_value' => 0,
            'condition' => 'baik',
        ])->assertRedirect(route('fleet.assets.index'));
        $this->get(route('fleet.assets.index'))->assertStatus(200)->assertSee('Komputer Kasir Utama');

        // Project & Material Realization
        $contractor = Customer::create(['name' => 'CV Kontraktor', 'type' => 'kontraktor', 'is_active' => true]);
        $project = Project::create([
            'customer_id' => $contractor->id,
            'name' => 'Renovasi Ruko 3 Lantai',
            'budget' => 5000000,
            'start_date' => date('Y-m-d'),
            'status' => 'active',
        ]);
        $this->post(route('project.projects.materials.store', $project->id), [
            'product_id' => $product->id,
            'qty' => 5,
            'unit_price' => 130000,
            'used_date' => date('Y-m-d'),
        ])->assertRedirect();
        $this->assertEquals(40, $product->fresh()->current_stock);
        $this->get(route('project.projects.show', $project->id))->assertStatus(200)->assertSee('Renovasi Ruko 3 Lantai');

        // Checklist Templates & Backup SQL
        $this->get(route('setting.checklists.index'))->assertStatus(200);
        $this->get(route('daily-reports.create'))->assertStatus(200);
        $this->get(route('daily-reports.index'))->assertStatus(200);
        $this->get(route('setting.backup.index'))->assertStatus(200);
        $this->get(route('setting.backup.download-sql'))->assertStatus(200);

        // Smoke-check all remaining ERP screens render cleanly
        $this->get(route('public-orders.index'))->assertStatus(200);
        $this->get(route('master.categories.index'))->assertStatus(200);
        $this->get(route('master.products.index'))->assertStatus(200);
        $this->get(route('master.products.barcode'))->assertStatus(200);
        $this->get(route('master.suppliers.index'))->assertStatus(200);
        $this->get(route('master.customers.index'))->assertStatus(200);
        $this->get(route('inventory.po.index'))->assertStatus(200);
        $this->get(route('inventory.po.create'))->assertStatus(200);
        $this->get(route('inventory.invoices.index'))->assertStatus(200);
        $this->get(route('inventory.returns.index'))->assertStatus(200);
        $this->get(route('inventory.returns.create'))->assertStatus(200);
        $this->get(route('finance.cash-ledger.index'))->assertStatus(200);
        $this->get(route('finance.cash-ledger.create'))->assertStatus(200);
        $this->get(route('hr.attendance.index'))->assertStatus(200);
        $this->get(route('hr.employees.index'))->assertStatus(200);
        $this->get(route('hr.payroll.index'))->assertStatus(200);
        $this->get(route('hr.payroll.slip', $payroll->id))->assertStatus(200);
        $this->get(route('fleet.vehicles.index'))->assertStatus(200);
        $this->get(route('fleet.jobs.index'))->assertStatus(200);
        $this->get(route('fleet.jobs.create'))->assertStatus(200);
        $this->get(route('project.projects.index'))->assertStatus(200);
        $this->get(route('setting.access.index'))->assertStatus(200);

        // Owner can create a new Kasir account from Akses & Sandi
        $newKasirEmail = 'kasir_new_' . Str::random(4) . '@sentosa.test';
        $this->post(route('setting.access.store'), [
            'name' => 'Kasir Baru',
            'email' => $newKasirEmail,
            'role' => 'kasir',
            'password' => 'kasir123',
        ])->assertRedirect(route('setting.access.index'));

        $this->post('/logout');
        $this->post('/login', [
            'email' => $newKasirEmail,
            'password' => 'kasir123',
        ])->assertRedirect('/dashboard');
    }

    public function test_kasir_dedicated_workspace_and_role_restrictions(): void
    {
        $kasir = $this->createStaffUser('kasir');
        $this->actingAs($kasir);

        // Dedicated Kasir Dashboard & Sidebar
        $this->get(route('dashboard'))
            ->assertStatus(200)
            ->assertSee('Workspace Khusus Kasir')
            ->assertSee('MODE KASIR')
            ->assertDontSee('Akses & Sandi')
            ->assertDontSee('Payroll & Bonus');

        // Allowed Kasir operational menus
        $this->get(route('reports.sales.index'))->assertStatus(200);
        $this->get(route('pos.session.index'))->assertStatus(200);
        $this->get(route('public-orders.index'))->assertStatus(200);
        $this->get(route('master.categories.index'))->assertStatus(200);
        $this->get(route('master.products.index'))->assertStatus(200);
        $this->get(route('master.customers.index'))->assertStatus(200);
        $this->get(route('inventory.po.index'))->assertStatus(200);
        $this->get(route('inventory.invoices.index'))->assertStatus(200);
        $this->get(route('inventory.returns.index'))->assertStatus(200);
        $this->get(route('finance.cash-ledger.index'))->assertStatus(200);
        $this->get(route('daily-reports.index'))->assertStatus(200);
        $this->get(route('hr.attendance.index'))->assertStatus(200);

        // Restricted Owner/Admin menus must return 403
        $this->get(route('hr.payroll.index'))->assertStatus(403);
        $this->get(route('hr.employees.index'))->assertStatus(403);
        $this->get(route('finance.wallets.index'))->assertStatus(403);
        $this->get(route('finance.cash-flow.index'))->assertStatus(403);
        $this->get(route('fleet.assets.index'))->assertStatus(403);
        $this->get(route('project.projects.index'))->assertStatus(403);
        $this->get(route('setting.targets.index'))->assertStatus(403);
        $this->get(route('setting.access.index'))->assertStatus(403);
        $this->get(route('setting.backup.index'))->assertStatus(403);
    }

    public function test_product_excel_template_export_and_import_with_initial_stock(): void
    {
        $owner = $this->createStaffUser('owner');
        $this->actingAs($owner);

        // Template & Export downloads succeed
        $this->get(route('master.products.template'))->assertStatus(200);
        $this->get(route('master.products.export'))->assertStatus(200);

        // CSV upload import creates products, auto-creates category, and records initial stock
        $csvContent = implode("\n", [
            'nama_produk,kategori,barcode,satuan,harga_beli,harga_jual,minimal_stok,stok_awal,deskripsi',
            '"Besi Beton 10mm","Besi & Baja","899000111222","Batang","Rp 62.000","Rp 75.000",10,45,"Besi SNI full"',
            '"Paku Kayu 5cm","Perkakas","899000111333","Kg",18000,24000,5,30,"Paku bangunan"',
        ]);

        $file = \Illuminate\Http\UploadedFile::fake()->createWithContent('produk.csv', $csvContent);

        $this->post(route('master.products.import'), ['file' => $file])
            ->assertRedirect(route('master.products.index'))
            ->assertSessionHas('success');

        $besi = Product::where('barcode', '899000111222')->first();
        $this->assertNotNull($besi);
        $this->assertEquals(62000.0, (float) $besi->purchase_price);
        $this->assertEquals(75000.0, (float) $besi->selling_price);
        $this->assertEquals(45, $besi->current_stock);
    }

    public function test_owner_template_19_column_and_master_csv_import(): void
    {
        $owner = $this->createStaffUser('owner');
        $this->actingAs($owner);

        // 1. Test Template 19-Kolom Owner (termasuk baris petunjuk di atas header & kalkulasi margin K50)
        $ownerTemplateCsv = implode("\n", [
            '"Wajib (01-16)","Wajib (Nama)","Wajib (Modal)","Opsional","Opsional","Opsional","Opsional","Opsional","Opsional","Opsional","Wajib","Isi 1","Wajib","Opsional","Opsional","Opsional","Opsional","Opsional","Opsional"',
            '"ID Kategori Angka","Nama Barang","Harga Beli","Nama Gudang","Nama Rak","Nama Supplier Utama","Nama Supplier 2","Stok Awal","Batas Minimum Stok","Target Maksimum Stok","Satuan 1 Terbesar","Rasio 1 (Harus 1)","Kode Margin 1 (Cth: K5)","Satuan 2 Sedang","Rasio 2 Ke Satuan 3","Kode Margin 2","Satuan 3 Terkecil","Rasio 3 Isi 1","Kode Margin 3"',
            '10,"Kawat Loket Test Owner 1/2x180",240000,"Gudang Utama","K4","PT Kawat Nusantara Test","",12,3,25,"ROLL",1,"K50","METER",42,"K50","","",""',
        ]);

        $templateFile = \Illuminate\Http\UploadedFile::fake()->createWithContent('template_owner.csv', $ownerTemplateCsv);
        $this->post(route('master.products.import'), ['file' => $templateFile])
            ->assertRedirect(route('master.products.index'))
            ->assertSessionHas('success');

        $kawat = Product::with(['category', 'supplier'])->where('name', 'Kawat Loket Test Owner 1/2x180')->first();
        $this->assertNotNull($kawat);
        $this->assertEquals('10', $kawat->category->code);
        $this->assertEquals('KAWAT, JARING DAN PENGIKAT', $kawat->category->name);
        $this->assertEquals('PT Kawat Nusantara Test', $kawat->supplier?->name);
        $this->assertEquals('Gudang Utama', $kawat->warehouse_name);
        $this->assertEquals('K4', $kawat->rack_location);
        $this->assertEquals(240000.0, (float) $kawat->purchase_price);
        $this->assertEquals('K50', $kawat->margin_code);
        $this->assertEquals(360000.0, (float) $kawat->selling_price);
        $this->assertEquals('METER', $kawat->secondary_unit);
        $this->assertEquals(42.0, (float) $kawat->secondary_ratio);
        $this->assertEquals(9000.0, (float) $kawat->secondary_price);
        $this->assertEquals(12, $kawat->current_stock);

        // 2. Test Master CSV Owner (Kategori + Supplier + Tabel Item Master)
        $masterCsv = implode("\n", [
            '"KODE","KATEGORI"',
            '"04","SELANG, PEMIPAAN & ACC"',
            '"",""',
            '"KODE","SUPLAYER / DISTRIBUTOR","HP"',
            '"SUP01","PT Pipa Sentosa Test","0812999888"',
            '"",""',
            '"NO","KTG","ID ITEM / BARCODE","ITEM","SATUAN","RUMUS","MODAL","KODE","HARGA JUAL","RAK","MAX","MIN","STOK"',
            '1,"04","899777666555","PIPA RUCIKA 4 INCH TEST","BTG","74000","Rp 74.000","K7,5","Rp 80.000","R-04",20,5,14',
        ]);

        $masterFile = \Illuminate\Http\UploadedFile::fake()->createWithContent('master_owner.csv', $masterCsv);
        $this->post(route('master.products.import'), ['file' => $masterFile])
            ->assertRedirect(route('master.products.index'))
            ->assertSessionHas('success');

        $pipa = Product::with('category')->where('barcode', '899777666555')->first();
        $this->assertNotNull($pipa);
        $this->assertEquals('PIPA RUCIKA 4 INCH TEST', $pipa->name);
        $this->assertEquals('04', $pipa->category->code);
        $this->assertEquals(74000.0, (float) $pipa->purchase_price);
        $this->assertEquals('K7,5', $pipa->margin_code);
        $this->assertEquals(80000.0, (float) $pipa->selling_price);
        $this->assertEquals('R-04', $pipa->rack_location);
        $this->assertEquals(20, $pipa->stock_maximum);
        $this->assertEquals(5, $pipa->stock_minimum);
        $this->assertEquals(14, $pipa->current_stock);

        // 3. Test Upload File .xlsx asli untuk data produk tanpa butuh ZipArchive
        $xlsxSpreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $xlsxSpreadsheet->getActiveSheet()->fromArray([
            ['nama_produk', 'kategori', 'barcode', 'satuan', 'harga_beli', 'harga_jual', 'minimal_stok', 'stok_awal', 'deskripsi'],
            ['Cat Dasar Test XLSX', 'CAT DAN KERAMIK & FINISHING', '899888777111', 'Kaleng', 45000, 60000, 5, 18, 'Upload dari .xlsx murni'],
        ]);
        $xlsxTemp = tempnam(sys_get_temp_dir(), 'sb_prod_') . '.xlsx';
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($xlsxSpreadsheet))->save($xlsxTemp);

        $xlsxUpload = new \Illuminate\Http\UploadedFile(
            $xlsxTemp,
            'Produk_Import.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );

        $this->post(route('master.products.import'), ['file' => $xlsxUpload])
            ->assertRedirect(route('master.products.index'))
            ->assertSessionHas('success');
        @unlink($xlsxTemp);

        $catDasar = Product::where('barcode', '899888777111')->first();
        $this->assertNotNull($catDasar);
        $this->assertEquals(60000.0, (float) $catDasar->selling_price);
        $this->assertEquals(18, $catDasar->current_stock);
    }

    public function test_qr_attendance_monthly_recap_and_full_salary_alpha_deduction(): void
    {
        $owner = $this->createStaffUser('owner');
        $karyawan = $this->createStaffUser('karyawan');

        // 1. Owner/Admin membuka Cetak Poster QR Permanen dan QR Harian
        $this->actingAs($owner);
        $this->get(route('hr.attendance.qr-kiosk'))
            ->assertStatus(200)
            ->assertSee('POSTER QR PERMANEN');
        $this->get(route('hr.attendance.qr-kiosk', ['mode' => 'daily']))->assertStatus(200);

        $qrSession = \App\Models\AttendanceQrSession::where('type', 'permanent')
            ->where('is_active', true)
            ->latest('id')
            ->first();
        $this->assertNotNull($qrSession);

        // Refresh QR Poster menghasilkan token permanen baru dan menonaktifkan token poster lama
        $oldToken = $qrSession->token;
        $this->post(route('hr.attendance.qr-refresh'), ['type' => 'permanent'])->assertRedirect();
        $newQrSession = \App\Models\AttendanceQrSession::where('type', 'permanent')
            ->where('is_active', true)
            ->latest('id')
            ->first();
        $this->assertNotNull($newQrSession);
        $this->assertNotEquals($oldToken, $newQrSession->token);

        // Simulasikan poster dicetak 5 hari lalu: QR permanen tetap berlaku setiap hari tanpa cetak ulang
        $newQrSession->update(['date' => \Carbon\Carbon::now('Asia/Pontianak')->subDays(5)->format('Y-m-d')]);

        // 2. Karyawan login diarahkan ke halaman Absensi & Scan QR
        $this->actingAs($karyawan);
        $this->get(route('dashboard'))->assertRedirect(route('hr.attendance.index'));
        $this->get(route('hr.attendance.index'))
            ->assertStatus(200)
            ->assertSee('Menu Karyawan')
            ->assertSee('Scan QR Absen Masuk');

        // Token lama/palsu ditolak
        $this->post(route('hr.attendance.scan-qr'), ['qr_token' => $oldToken])
            ->assertSessionHas('error');

        // Scan token QR aktif pertama kali = Clock In via QR
        $this->post(route('hr.attendance.scan-qr'), ['qr_token' => $newQrSession->token])
            ->assertSessionHas('success');

        $today = \Carbon\Carbon::today('Asia/Pontianak')->format('Y-m-d');
        $att = \App\Models\Attendance::where('user_id', $karyawan->id)->whereDate('date', $today)->first();
        $this->assertNotNull($att);
        $this->assertEquals('hadir', $att->status);
        $this->assertEquals('qr', $att->check_in_method);
        $this->assertNull($att->clock_out);

        // Scan token QR aktif kedua kali = Clock Out via QR
        $this->post(route('hr.attendance.scan-qr'), ['qr_token' => $newQrSession->token])
            ->assertSessionHas('success');
        $att->refresh();
        $this->assertNotNull($att->clock_out);

        // 3. Owner melihat rekap harian & bulanan, mencatat 1 hari Alpha/Bolos, dan export Excel rekap bulanan
        $this->actingAs($owner);
        $now = \Carbon\Carbon::now('Asia/Pontianak');
        $this->get(route('hr.attendance.index', ['month' => $now->month, 'year' => $now->year]))
            ->assertStatus(200)
            ->assertSee($karyawan->name)
            ->assertSee('Rekap Absensi Bulanan');

        $alphaDate = $now->copy()->subDays(2)->format('Y-m-d');
        if ($alphaDate === $today) {
            $alphaDate = $now->copy()->subDays(1)->format('Y-m-d');
        }
        $alphaCarbon = \Carbon\Carbon::parse($alphaDate, 'Asia/Pontianak');

        $this->post(route('hr.attendance.mark-status'), [
            'user_id' => $karyawan->id,
            'date' => $alphaDate,
            'status' => 'alpha',
            'notes' => 'Bolos tanpa kabar',
        ])->assertSessionHas('success');

        $this->get(route('hr.attendance.export-monthly', ['month' => $now->month, 'year' => $now->year]))
            ->assertStatus(200);

        // 4. Owner memasang Gaji Full Bulanan + tarif potongan Alpha lalu generate payroll bulan tanggal Alpha
        $this->put(route('hr.payroll.salary-config', $karyawan->id), [
            'monthly_salary' => 3000000,
            'absence_deduction_per_day' => 150000,
            'daily_salary' => 115000,
        ])->assertSessionHas('success');

        $this->post(route('hr.payroll.generate'), [
            'month' => $alphaCarbon->month,
            'year' => $alphaCarbon->year,
            'working_days' => 26,
        ])->assertSessionHas('success');

        $payroll = \App\Models\Payroll::where('user_id', $karyawan->id)
            ->where('month', $alphaCarbon->month)
            ->where('year', $alphaCarbon->year)
            ->first();
        $this->assertNotNull($payroll);
        $this->assertEquals(3000000.0, (float) $payroll->monthly_salary_base);
        $this->assertEquals(1, $payroll->alpha_days);
        $this->assertEquals(150000.0, (float) $payroll->alpha_deduction);
        $this->assertEquals(2850000.0, (float) $payroll->net_salary);

        $this->get(route('hr.payroll.slip', $payroll->id))
            ->assertStatus(200)
            ->assertSee('Potongan Bolos / Alpha (1 hari)');
    }

    public function test_product_image_bulk_import_from_excel_with_embedded_drawings(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');

        $admin = $this->createStaffUser('admin');
        $this->actingAs($admin);

        $cat = Category::create(['name' => 'Cat Image Test ' . uniqid(), 'is_active' => true]);
        $prodWithBarcode = Product::create([
            'category_id' => $cat->id,
            'barcode' => 'IMG-899001122',
            'name' => 'Produk Gambar Test PNG',
            'unit' => 'Pcs',
            'purchase_price' => 10000,
            'selling_price' => 15000,
            'stock_minimum' => 1,
            'is_active' => true,
        ]);
        $prodNoBarcode = Product::create([
            'category_id' => $cat->id,
            'barcode' => null,
            'name' => 'Produk Gambar Test JPG',
            'unit' => 'Kaleng',
            'purchase_price' => 20000,
            'selling_price' => 30000,
            'stock_minimum' => 1,
            'is_active' => true,
        ]);

        // 1. Download template Excel gambar berjalan lancar
        $this->get(route('master.products.images.template'))->assertStatus(200);

        // 2. Buat file .xlsx nyata berisi 2 produk dan 2 gambar tertanam (PNG & JPG)
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([
            ['kode_barang', 'nama_produk', 'status_foto_saat_ini', 'foto_produk'],
            [$prodWithBarcode->barcode, $prodWithBarcode->name, 'Belum Ada Foto', ''],
            ['PRD-' . $prodNoBarcode->id, $prodNoBarcode->name, 'Belum Ada Foto', ''],
        ]);

        // Gambar PNG untuk baris 2
        $pngImg = imagecreatetruecolor(24, 24);
        $pngTmp = tempnam(sys_get_temp_dir(), 'sb_png_') . '.png';
        imagepng($pngImg, $pngTmp);
        imagedestroy($pngImg);

        $drawing1 = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
        $drawing1->setName('Produk PNG');
        $drawing1->setPath($pngTmp);
        $drawing1->setCoordinates('D2');
        $drawing1->setWorksheet($sheet);

        // Gambar JPG untuk baris 3
        $jpgImg = imagecreatetruecolor(24, 24);
        $jpgTmp = tempnam(sys_get_temp_dir(), 'sb_jpg_') . '.jpg';
        imagejpeg($jpgImg, $jpgTmp, 85);
        imagedestroy($jpgImg);

        $drawing2 = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
        $drawing2->setName('Produk JPG');
        $drawing2->setPath($jpgTmp);
        $drawing2->setCoordinates('D3');
        $drawing2->setWorksheet($sheet);

        $xlsxPath = tempnam(sys_get_temp_dir(), 'sb_img_xlsx_') . '.xlsx';
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($xlsxPath);

        $uploadedXlsx = new \Illuminate\Http\UploadedFile(
            $xlsxPath,
            'Import_Gambar_Produk.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );

        $this->post(route('master.products.images.import'), ['file' => $uploadedXlsx])
            ->assertRedirect(route('master.products.index'))
            ->assertSessionHas('success');

        @unlink($pngTmp);
        @unlink($jpgTmp);
        @unlink($xlsxPath);

        $prodWithBarcode->refresh();
        $prodNoBarcode->refresh();

        $this->assertNotNull($prodWithBarcode->image);
        $this->assertStringEndsWith('.png', $prodWithBarcode->image);
        \Illuminate\Support\Facades\Storage::disk('public')->assertExists($prodWithBarcode->image);

        $this->assertNotNull($prodNoBarcode->image);
        $this->assertStringEndsWith('.jpg', $prodNoBarcode->image);
        \Illuminate\Support\Facades\Storage::disk('public')->assertExists($prodNoBarcode->image);

        // 3. Test Upload Banyak Foto Sekaligus (Nama File = Kode Barang)
        $batchPng = \Illuminate\Http\UploadedFile::fake()->image('IMG-899001122.png', 40, 40);
        $batchJpg = \Illuminate\Http\UploadedFile::fake()->image('PRD-' . $prodNoBarcode->id . '.jpg', 40, 40);

        $this->post(route('master.products.images.batch'), [
            'images' => [$batchPng, $batchJpg],
        ])->assertRedirect(route('master.products.index'))
          ->assertSessionHas('success');

        $prodWithBarcode->refresh();
        $prodNoBarcode->refresh();
        \Illuminate\Support\Facades\Storage::disk('public')->assertExists($prodWithBarcode->image);
        \Illuminate\Support\Facades\Storage::disk('public')->assertExists($prodNoBarcode->image);
    }

    public function test_receipt_null_safety_and_resource_show_routes_do_not_500(): void
    {
        $owner = $this->createStaffUser('owner');
        $this->actingAs($owner);

        $session = \App\Models\CashSession::create([
            'user_id' => $owner->id,
            'start_time' => now(),
            'opening_balance' => 100000,
            'status' => 'open',
        ]);

        // Sale tanpa baris payment tidak boleh menyebabkan 500 saat cetak struk
        $sale = \App\Models\Sale::create([
            'invoice_number' => 'INV-NULLSAFE-' . uniqid(),
            'idempotency_key' => 'key-' . uniqid(),
            'cash_session_id' => $session->id,
            'user_id' => $owner->id,
            'total_amount' => 25000,
            'discount_amount' => 0,
            'status' => 'completed',
        ]);

        $this->get(route('pos.receipt', $sale->id))
            ->assertStatus(200)
            ->assertSee($sale->invoice_number);

        // Route show yang dihapus tidak boleh memicu Method does not exist (500)
        $cat = Category::create(['name' => 'Cat No Show ' . uniqid(), 'is_active' => true]);
        $this->assertNotEquals(500, $this->get('/master/categories/' . $cat->id)->getStatusCode());
    }

    public function test_all_in_one_excel_data_stock_image_and_manual_initial_stock(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');

        $admin = $this->createStaffUser('admin');
        $this->actingAs($admin);
        $cat = Category::create(['name' => 'Semen AllInOne ' . uniqid(), 'is_active' => true]);

        // 1. Form tambah & edit produk kini mendukung Stok Awal / Penyesuaian Stok langsung
        $this->post(route('master.products.store'), [
            'category_id' => $cat->id,
            'barcode' => 'ALLIN-001',
            'name' => 'Semen Manual Stok',
            'unit' => 'Sak',
            'purchase_price' => 52000,
            'selling_price' => 60000,
            'stock_minimum' => 10,
            'initial_stock' => 75,
            'is_active' => '1',
        ])->assertRedirect(route('master.products.index'));

        $manualProd = Product::where('barcode', 'ALLIN-001')->first();
        $this->assertNotNull($manualProd);
        $this->assertEquals(75, $manualProd->current_stock);

        $this->put(route('master.products.update', $manualProd->id), [
            'category_id' => $cat->id,
            'barcode' => 'ALLIN-001',
            'name' => 'Semen Manual Stok',
            'unit' => 'Sak',
            'purchase_price' => 53000,
            'selling_price' => 62000,
            'stock_minimum' => 10,
            'initial_stock' => 90,
            'is_active' => '1',
        ])->assertRedirect(route('master.products.index'));

        $manualProd->refresh();
        $this->assertEquals(90, $manualProd->current_stock);

        // 2. Satu upload file .xlsx via Import Excel langsung membuat produk + mengisi stok + menyimpan gambar!
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([
            ['kode_barang', 'nama_produk', 'kategori', 'satuan', 'harga_beli', 'harga_jual', 'stok', 'minimal_stok', 'foto_produk'],
            ['ALLIN-XLSX-999', 'Semen All In One Excel', $cat->name, 'Sak', 50000, 61000, 120, 15, ''],
        ]);

        $pngImg = imagecreatetruecolor(24, 24);
        $pngTmp = tempnam(sys_get_temp_dir(), 'sb_aio_') . '.png';
        imagepng($pngImg, $pngTmp);
        imagedestroy($pngImg);

        $drawing = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
        $drawing->setName('Foto Semen');
        $drawing->setPath($pngTmp);
        $drawing->setCoordinates('I2');
        $drawing->setWorksheet($sheet);

        $xlsxPath = tempnam(sys_get_temp_dir(), 'sb_aio_xlsx_') . '.xlsx';
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save($xlsxPath);

        $uploaded = new \Illuminate\Http\UploadedFile(
            $xlsxPath,
            'All_In_One_Produk_Stok_Gambar.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );

        $this->post(route('master.products.import'), ['file' => $uploaded])
            ->assertRedirect(route('master.products.index'))
            ->assertSessionHas('success');

        @unlink($pngTmp);
        @unlink($xlsxPath);

        $aioProd = Product::where('barcode', 'ALLIN-XLSX-999')->first();
        $this->assertNotNull($aioProd);
        $this->assertEquals(61000.0, (float) $aioProd->selling_price);
        $this->assertEquals(120, $aioProd->current_stock);
        $this->assertNotNull($aioProd->image);
        \Illuminate\Support\Facades\Storage::disk('public')->assertExists($aioProd->image);
    }

    public function test_role_visibility_for_targets_and_friendly_403_page(): void
    {
        $owner = $this->createStaffUser('owner');
        $admin = $this->createStaffUser('admin');
        $gudang = $this->createStaffUser('gudang');
        $driver = $this->createStaffUser('driver');

        $this->actingAs($owner)->get(route('setting.targets.index'))->assertStatus(200);
        $this->actingAs($admin)->get(route('setting.targets.index'))->assertStatus(200);

        // Role non-owner/admin tidak melihat link target/payroll di sidebar maupun dashboard, dan mendapat halaman 403 ramah
        $this->actingAs($gudang)->get(route('dashboard'))
            ->assertStatus(200)
            ->assertDontSee(route('setting.targets.index'))
            ->assertDontSee('Atur Target');
        $this->actingAs($driver)->get(route('dashboard'))
            ->assertStatus(200)
            ->assertDontSee(route('setting.targets.index'))
            ->assertDontSee('Atur Target');

        $this->actingAs($gudang)->get(route('setting.targets.index'))
            ->assertStatus(403)
            ->assertSee('Akses Dibatasi (403)');

        // Menu Buka POS / Kasir hanya untuk Owner, Admin, Kasir — disembunyikan & diblokir untuk Karyawan, Gudang, Driver
        $karyawan = $this->createStaffUser('karyawan');
        $kasir = $this->createStaffUser('kasir');

        $this->actingAs($karyawan)->get(route('hr.attendance.index'))
            ->assertStatus(200)
            ->assertDontSee('Buka POS');
        $this->actingAs($karyawan)->get(route('pos.session.index'))->assertStatus(403);
        $this->actingAs($gudang)->get(route('dashboard'))->assertStatus(200)->assertDontSee('Buka POS');
        $this->actingAs($gudang)->get(route('pos.session.index'))->assertStatus(403);
        $this->actingAs($driver)->get(route('pos.session.index'))->assertStatus(403);

        $this->actingAs($owner)->get(route('pos.session.index'))->assertStatus(200);
        $this->actingAs($admin)->get(route('pos.session.index'))->assertStatus(200);
        $this->actingAs($kasir)->get(route('pos.session.index'))->assertStatus(200);
    }

    public function test_sentosa_favicon_is_present_and_linked_in_all_layouts(): void
    {
        $this->assertFileExists(public_path('favicon.ico'));
        $this->assertGreaterThan(0, filesize(public_path('favicon.ico')));
        $this->assertFileExists(public_path('images/favicon.png'));
        $this->assertGreaterThan(0, filesize(public_path('images/favicon.png')));

        $this->get(route('catalog.index'))->assertStatus(200)->assertSee('images/favicon.png', false);
        $this->get(route('login'))->assertStatus(200)->assertSee('images/favicon.png', false);

        $owner = $this->createStaffUser('owner');
        $this->actingAs($owner)->get(route('dashboard'))->assertStatus(200)->assertSee('images/favicon.png', false);

        $karyawan = $this->createStaffUser('karyawan');
        $this->actingAs($karyawan)->get(route('setting.targets.index'))
            ->assertStatus(403)
            ->assertSee('images/favicon.png', false);
    }

    public function test_ui_and_form_fixes_work_end_to_end(): void
    {
        $owner = $this->createStaffUser('owner');
        $kasir = $this->createStaffUser('kasir');

        // 1. Form produk menyimpan rack_location, margin_code, secondary_unit, secondary_price
        $cat = Category::create(['name' => 'Cat Form Fix ' . uniqid(), 'is_active' => true]);
        $this->actingAs($owner)->post(route('master.products.store'), [
            'category_id' => $cat->id,
            'name' => 'Kawat Beton Gulung',
            'barcode' => 'FIX-001',
            'unit' => 'Roll',
            'purchase_price' => 100000,
            'selling_price' => 125000,
            'stock_minimum' => 5,
            'initial_stock' => 20,
            'rack_location' => 'R-09',
            'margin_code' => 'K25',
            'secondary_unit' => 'Kg',
            'secondary_price' => 18000,
            'is_active' => '1',
        ])->assertRedirect(route('master.products.index'));

        $product = Product::where('barcode', 'FIX-001')->firstOrFail();
        $this->assertEquals('R-09', $product->rack_location);
        $this->assertEquals('K25', $product->margin_code);
        $this->assertEquals('Kg', $product->secondary_unit);
        $this->assertEquals(18000.0, (float) $product->secondary_price);

        $this->get(route('master.products.index'))
            ->assertStatus(200)
            ->assertSee('R-09')
            ->assertSee('K25')
            ->assertSee('Ecer Kg: Rp 18.000');

        // 2. Kasir tidak melihat tombol khusus Owner/Admin yang menyebabkan 403
        $this->actingAs($kasir)->get(route('finance.cash-ledger.index'))
            ->assertStatus(200)
            ->assertDontSee('Laporan Arus Kas');
        $this->actingAs($kasir)->get(route('daily-reports.create'))
            ->assertStatus(200)
            ->assertDontSee('Kelola Master Ceklis');

        $this->actingAs($owner)->get(route('finance.cash-ledger.index'))
            ->assertStatus(200)
            ->assertSee('Laporan Arus Kas');
        $this->actingAs($owner)->get(route('daily-reports.create'))
            ->assertStatus(200)
            ->assertSee('Kelola Master Ceklis');

        // 3. Payroll salary-config memakai form attribute valid
        $this->actingAs($owner)->get(route('hr.payroll.index'))
            ->assertStatus(200)
            ->assertSee('form="salary-config-' . $kasir->id . '"', false);

        // 4. POS JSON validation mengembalikan 422 JSON saat item kosong
        $this->actingAs($kasir)->post(route('pos.session.open'), ['opening_balance' => 100000]);
        $this->postJson(route('pos.store'), ['items' => []])
            ->assertStatus(422)
            ->assertJsonStructure(['message', 'errors']);

        // 5. Katalog publik menyertakan stok pada server cart
        $pelanggan = $this->createStaffUser('pelanggan');
        \App\Models\CartItem::create([
            'user_id' => $pelanggan->id,
            'product_id' => $product->id,
            'qty' => 2,
        ]);
        $this->actingAs($pelanggan)->get(route('catalog.index'))
            ->assertStatus(200)
            ->assertSee('"stock":20', false);

        // 6. Halaman login Pembeli (/masuk) terpisah penuh & aman dari halaman Staff/Owner (/login)
        $this->post('/logout');
        $this->get(route('catalog.index'))
            ->assertStatus(200)
            ->assertSee('Masuk Pembeli')
            ->assertSee(route('customer.login'));

        $this->get(route('customer.login'))
            ->assertStatus(200)
            ->assertSee('Masuk Akun Pembeli')
            ->assertDontSee('owner@sentosa.test')
            ->assertDontSee('Isi Cepat');

        $this->get(route('login'))
            ->assertStatus(200)
            ->assertSee('PORTAL INTERNAL TOKO')
            ->assertDontSee('owner@sentosa.test')
            ->assertDontSee('Isi Cepat')
            ->assertDontSee('Daftar Pembeli');

        // Staff/Owner tidak bisa login lewat halaman Pembeli (/masuk)
        $this->post(route('customer.login'), [
            'email' => $owner->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');
        $this->assertGuest();

        // Pembeli tidak bisa login lewat halaman Staff (/login), harus lewat /masuk
        $this->post(route('login'), [
            'email' => $pelanggan->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->post(route('customer.login'), [
            'email' => $pelanggan->email,
            'password' => 'password',
        ])->assertRedirect(route('catalog.account'));

        $this->get(route('catalog.account'))
            ->assertStatus(200)
            ->assertSee('PORTAL PEMBELI')
            ->assertSee('Bintang Gold')
            ->assertSee('Saldo Deposit');

        $this->post('/logout')->assertRedirect(route('catalog.index'));
    }

    public function test_qr_web_pelanggan_inventory_opname_fleet_customer_deposit_pos_and_owner_project(): void
    {
        $owner = $this->createStaffUser('owner');
        $kasir = $this->createStaffUser('kasir');
        $karyawan = $this->createStaffUser('karyawan');

        // 1. QR Web Pelanggan bisa dibuka Owner, Kasir, maupun Karyawan
        foreach ([$owner, $kasir, $karyawan] as $staff) {
            $this->actingAs($staff)->get(route('catalog.qr'))
                ->assertStatus(200)
                ->assertSee('QR Web Pelanggan')
                ->assertSee(route('catalog.index'));
        }

        // 2. Inventori Kartu Stok & Stock Opname
        $this->actingAs($owner);
        $cat = Category::create(['name' => 'Cat Opname ' . uniqid(), 'is_active' => true]);
        $prod = Product::create([
            'category_id' => $cat->id,
            'name' => 'Besi Beton Opname',
            'unit' => 'Batang',
            'purchase_price' => 50000,
            'selling_price' => 65000,
            'stock_minimum' => 5,
            'is_active' => true,
        ]);
        StockLedger::create([
            'product_id' => $prod->id,
            'user_id' => $owner->id,
            'type' => 'in',
            'qty' => 10,
            'stock_before' => 0,
            'stock_after' => 10,
            'description' => 'Stok awal test',
        ]);

        $this->post(route('inventory.stock.opname'), [
            'product_id' => $prod->id,
            'actual_stock' => 15,
            'notes' => 'Tambah fisik rak B',
        ])->assertSessionHas('success');
        $this->assertEquals(15, Product::withStock()->find($prod->id)->current_stock);

        $this->post(route('inventory.stock.opname'), [
            'product_id' => $prod->id,
            'actual_stock' => 12,
            'notes' => 'Rusak 3 batang',
        ])->assertSessionHas('success');
        $this->assertEquals(12, Product::withStock()->find($prod->id)->current_stock);

        $this->get(route('inventory.stock.index', ['product_id' => $prod->id]))
            ->assertStatus(200)
            ->assertSee('Stock Opname: Rusak 3 batang');

        // 3. Pelanggan Detail & Bayar POS Pakai Deposit
        $cust = Customer::create([
            'name' => 'Kontraktor Deposit Test',
            'type' => 'kontraktor',
            'phone' => '08111222333',
            'deposit_balance' => 0,
            'is_active' => true,
        ]);
        $this->post(route('master.customers.deposit', $cust->id), [
            'amount' => 500000,
            'description' => 'Titip awal proyek',
        ])->assertSessionHas('success');

        $this->actingAs($kasir)->post(route('pos.session.open'), ['opening_balance' => 100000]);
        $this->postJson(route('pos.store'), [
            'idempotency_key' => 'dep-' . Str::uuid(),
            'items' => [['product_id' => $prod->id, 'qty' => 2]],
            'payment_method' => 'deposit',
            'paid_amount' => 130000,
            'customer_id' => $cust->id,
        ])->assertOk()->assertJson(['status' => 'success']);

        $cust->refresh();
        $this->assertEquals(370000.0, (float) $cust->deposit_balance);
        $this->assertEquals(10, Product::withStock()->find($prod->id)->current_stock);

        $this->actingAs($owner)->get(route('master.customers.show', $cust->id))
            ->assertStatus(200)
            ->assertSee('Kontraktor Deposit Test')
            ->assertSee('Titip awal proyek')
            ->assertSee('Pembayaran POS:');

        // 4. Angkutan / Armada dengan filter & prefill dari Pesanan Online
        $order = PublicOrder::create([
            'order_code' => 'PUB-TEST-' . strtoupper(Str::random(4)),
            'customer_name' => 'Pemesan Online Armada',
            'phone' => '08999888777',
            'shipping_method' => 'antar',
            'address' => 'Jl. Tanjungpura No. 5',
            'shipping_cost' => 25000,
            'payment_method' => 'cash',
            'total_amount' => 90000,
            'status' => 'packed',
        ]);
        $this->get(route('fleet.jobs.create', ['order_id' => $order->id]))
            ->assertStatus(200)
            ->assertSee($order->order_code);
        $this->get(route('fleet.jobs.index'))
            ->assertStatus(200)
            ->assertSee('Laba Bersih Angkutan');

        // 5. Project Internal Owner (tanpa kontraktor) & Retur Sisa Material
        $this->post(route('project.projects.store'), [
            'customer_id' => '',
            'name' => 'Bangun Gudang Owner',
            'budget' => 25000000,
            'start_date' => date('Y-m-d'),
            'status' => 'active',
        ])->assertRedirect(route('project.projects.index'));

        $project = Project::where('name', 'Bangun Gudang Owner')->firstOrFail();
        $this->assertNull($project->customer_id);

        $this->get(route('project.projects.index'))
            ->assertStatus(200)
            ->assertSee('Proyek Internal Owner');

        $this->post(route('project.projects.materials.store', $project->id), [
            'product_id' => $prod->id,
            'qty' => 4,
            'unit_price' => 65000,
            'used_date' => date('Y-m-d'),
            'notes' => 'Ambil tahap 1',
        ])->assertSessionHas('success');
        $this->assertEquals(6, Product::withStock()->find($prod->id)->current_stock);

        $this->post(route('project.projects.materials.return', $project->id), [
            'product_id' => $prod->id,
            'qty' => 1,
            'used_date' => date('Y-m-d'),
            'notes' => 'Sisa 1 batang utuh',
        ])->assertSessionHas('success');

        $this->assertEquals(7, Product::withStock()->find($prod->id)->current_stock);
        $this->assertEquals(195000.0, $project->fresh()->total_material_cost);

        // 6. Strategi & Target Bisnis (Omzet, Profit, Batas Biaya, Kategori Fokus, Fast/Slow Moving) + Follow-Up WA
        $now = \Carbon\Carbon::now('Asia/Pontianak');
        $this->actingAs($owner)->post(route('setting.targets.store'), [
            'month' => $now->month,
            'year' => $now->year,
            'target_amount' => 50000000,
            'target_profit' => 12000000,
            'max_expense' => 4000000,
            'working_days' => 25,
            'focus_category_id' => $cat->id,
            'notes' => 'Target Ekspansi Q4',
            'strategy_plan' => 'Dorong penjualan besi beton dan follow-up kontraktor',
        ])->assertSessionHas('success');

        $target = SalesTarget::where('month', $now->month)->where('year', $now->year)->firstOrFail();
        $this->assertEquals(12000000.0, (float) $target->target_profit);
        $this->assertEquals(4000000.0, (float) $target->max_expense);
        $this->assertEquals(25, $target->working_days);
        $this->assertEquals($cat->id, $target->focus_category_id);

        $this->get(route('setting.targets.index'))
            ->assertStatus(200)
            ->assertSee('Strategi &amp; Target Bisnis', false)
            ->assertSee('Dorong penjualan besi beton dan follow-up kontraktor')
            ->assertSee('Produk Terlaris (30 Hari)')
            ->assertSee('Barang Mengendap (30 Hari)');

        $this->get(route('dashboard'))
            ->assertStatus(200)
            ->assertSee('Pace ideal:')
            ->assertSee('Fokus: ' . $cat->name);

        $this->get(route('master.customers.show', $cust->id))
            ->assertStatus(200)
            ->assertSee('https://wa.me/628111222333', false);
    }

    public function test_store_settings_multi_unit_pos_void_role_workspaces_and_waybill(): void
    {
        $owner = $this->createStaffUser('owner');
        $kasir = $this->createStaffUser('kasir');
        $gudang = $this->createStaffUser('gudang');
        $driver = $this->createStaffUser('driver');

        $this->actingAs($owner);

        // 1. Store settings & dynamic bank accounts
        $setting = \App\Models\StoreSetting::current();
        $this->assertStringContainsString('Amboyo Inti', $setting->store_address);

        $this->get(route('setting.store.index'))
            ->assertStatus(200)
            ->assertSee($setting->store_address);

        $this->post(route('finance.wallets.store'), [
            'name' => 'BCA Toko Sentosa',
            'type' => 'bank',
            'account_number' => '8881234567',
            'account_holder' => 'Nelson Sentosa',
            'initial_balance' => 0,
            'show_on_catalog' => '1',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->post(route('finance.wallets.store'), [
            'name' => 'BRI Cadangan Hidden',
            'type' => 'bank',
            'account_number' => '999000111',
            'account_holder' => 'Hidden Holder',
            'initial_balance' => 0,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $cat = Category::create(['name' => 'Material Eceran', 'slug' => 'material-eceran-' . Str::random(5)]);
        $product = Product::create([
            'category_id' => $cat->id,
            'barcode' => 'BRG-' . Str::random(6),
            'name' => 'Semen Gresik 40Kg',
            'unit' => 'Sak',
            'secondary_unit' => 'Kg',
            'secondary_ratio' => 40,
            'secondary_price' => 2500,
            'purchase_price' => 50000,
            'selling_price' => 65000,
            'stock_minimum' => 2,
            'is_active' => true,
        ]);

        StockLedger::create([
            'product_id' => $product->id,
            'user_id' => $owner->id,
            'type' => 'in',
            'qty' => 10,
            'stock_before' => 0,
            'stock_after' => 10,
            'description' => 'Stok Awal Eceran',
        ]);

        $order = PublicOrder::create([
            'order_code' => 'ORD-' . strtoupper(Str::random(6)),
            'customer_name' => 'Budi Online',
            'phone' => '08123456789',
            'shipping_method' => 'antar',
            'address' => 'Ngabang',
            'payment_method' => 'transfer',
            'total_amount' => 65000,
            'status' => 'pending',
        ]);

        $this->get(route('catalog.success', $order->order_code))
            ->assertStatus(200)
            ->assertSee('8881234567')
            ->assertSee('Nelson Sentosa')
            ->assertDontSee('999000111');

        // Toggle show_customer_accounts off & update address note
        $this->put(route('setting.store.update'), [
            'store_name' => 'Sentosa Bangunan',
            'store_address' => \App\Models\StoreSetting::DEFAULT_ADDRESS,
            'store_phone' => '081255550000',
            'bank_transfer_note' => 'Kirim bukti transfer ke WA kasir.',
        ])->assertRedirect();

        $this->get(route('catalog.index'))
            ->assertStatus(200)
            ->assertDontSee('Masuk Pembeli');

        // 2. Pending count polling endpoint
        $this->getJson(route('public-orders.pending-count'))
            ->assertStatus(200)
            ->assertJsonStructure(['pending_count']);

        // 3. Multi-unit POS transaction + receipt address + void/return restore
        $this->actingAs($kasir);
        CashSession::create([
            'user_id' => $kasir->id,
            'start_time' => now(),
            'opening_balance' => 100000,
            'status' => 'open',
        ]);
        $cashWallet = Wallet::where('type', 'cash')->first() ?: Wallet::create([
            'name' => 'Kas Laci',
            'type' => 'cash',
            'initial_balance' => 0,
            'is_active' => true,
        ]);

        $posRes = $this->postJson(route('pos.store'), [
            'idempotency_key' => (string) Str::uuid(),
            'items' => [
                ['product_id' => $product->id, 'qty' => 40, 'unit_type' => 'secondary'],
            ],
            'payment_method' => 'cash',
            'wallet_id' => $cashWallet->id,
            'paid_amount' => 100000,
        ]);
        $posRes->assertStatus(200)->assertJson(['status' => 'success']);
        $saleId = (int) basename((string) $posRes->json('redirect'));

        $sale = \App\Models\Sale::with('items')->findOrFail($saleId);
        $this->assertEquals(100000, (float) $sale->total_amount);
        $this->assertEquals('Kg', $sale->items->first()->unit_label);
        $this->assertEquals(9, Product::withStock()->find($product->id)->current_stock);

        $this->get(route('pos.receipt', $sale->id))
            ->assertStatus(200)
            ->assertSee('Amboyo Inti')
            ->assertSee('Kg');

        $this->post(route('pos.void', $sale->id), [
            'reason' => 'Salah input qty eceran',
        ])->assertRedirect();

        $this->assertEquals('cancelled', $sale->fresh()->status);
        $this->assertEquals(10, Product::withStock()->find($product->id)->current_stock);

        // 4. Dedicated Gudang & Driver workspaces + Printable Waybill
        $this->actingAs($gudang)
            ->get(route('dashboard'))
            ->assertStatus(200)
            ->assertSee('Workspace Operasional Gudang')
            ->assertSee('Dashboard Gudang');

        $vehicle = \App\Models\Vehicle::create([
            'name' => 'Pickup L300',
            'plate_number' => 'KB 8899 XX',
            'type' => 'internal',
            'is_active' => true,
        ]);
        $job = \App\Models\TransportJob::create([
            'vehicle_id' => $vehicle->id,
            'driver_id' => $driver->id,
            'destination' => 'Desa Hilir Kantor Ngabang',
            'notes' => '10 Sak Semen',
            'status' => 'on_trip',
            'departure_date' => now(),
        ]);

        $this->actingAs($driver)
            ->get(route('dashboard'))
            ->assertStatus(200)
            ->assertSee('Workspace Driver &amp; Pengiriman', false)
            ->assertSee('Dashboard Driver');

        $this->actingAs($driver)
            ->get(route('fleet.jobs.waybill', $job->id))
            ->assertStatus(200)
            ->assertSee('Surat Jalan Armada')
            ->assertSee('KB 8899 XX')
            ->assertSee('Amboyo Inti');
    }

    public function test_owner_excel_importers_customers_bon_fleet_rates_projects_and_employees(): void
    {
        $owner = $this->createStaffUser('owner');
        $this->actingAs($owner);

        // 1. Import Pelanggan (format BON PELANGGAN) + Lunasi Bon ke Buku Kas
        $custCsv = "Nama Pelanggan,ALAMAT,NOMOR HP,Total Belanja,deposit,SISA BON\n"
            . "CV Karya Mandiri,Ngabang,081299990001,15000000,500000,250000\n"
            . "Pak Anton Bangunan,Amboyo,081299990002,3000000,0,100000\n";
        $custFile = \Illuminate\Http\UploadedFile::fake()->createWithContent('bon_pelanggan.csv', $custCsv);

        $this->post(route('master.customers.import'), ['file' => $custFile])
            ->assertRedirect()
            ->assertSessionHas('success');

        $karya = Customer::where('name', 'CV Karya Mandiri')->firstOrFail();
        $this->assertEquals('kontraktor', $karya->type);
        $this->assertEquals(500000, (float) $karya->deposit_balance);
        $this->assertEquals(250000, (float) $karya->debt_balance);

        $wallet = Wallet::where('is_active', true)->first() ?: Wallet::create([
            'name' => 'Kas Utama',
            'type' => 'cash',
            'initial_balance' => 0,
            'is_active' => true,
        ]);

        $this->post(route('master.customers.debt', $karya->id), [
            'action_type' => 'out',
            'amount' => 150000,
            'wallet_id' => $wallet->id,
            'description' => 'Cicilan bon tahap 1',
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertEquals(100000, (float) $karya->fresh()->debt_balance);
        $this->assertDatabaseHas('cash_ledgers', [
            'category' => 'pelunasan_bon_pelanggan',
            'amount' => 150000,
        ]);

        $this->get(route('master.customers.show', $karya->id))
            ->assertStatus(200)
            ->assertSee('Riwayat Mutasi Bon')
            ->assertSee('Cicilan bon tahap 1');

        // 2. Import Tarif Angkutan (format sheet HARGA ANGKUTAN) + simpan rate manual
        $fleetCsv = "NO,ASAL,TUJUAN,4 KUBIK,6 KUBIK,KETERANGAN\n"
            . "1,SELABA,PASAR AREA,150000,225000,Kota Ngabang\n"
            . "2,,SINGKOPEK,250000,375000,\n";
        $fleetFile = \Illuminate\Http\UploadedFile::fake()->createWithContent('angkutan.csv', $fleetCsv);

        $this->post(route('fleet.jobs.import'), ['file' => $fleetFile])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('transport_rates', [
            'origin' => 'SELABA',
            'destination' => 'SINGKOPEK',
            'rate_4_m3' => 250000,
            'rate_6_m3' => 375000,
        ]);

        $this->post(route('fleet.rates.store'), [
            'origin' => 'TOKO SENTOSA',
            'destination' => 'AMBOYO INTI',
            'rate_4_m3' => 100000,
            'rate_6_m3' => 150000,
        ])->assertRedirect()->assertSessionHas('success');

        $this->get(route('fleet.jobs.create'))
            ->assertStatus(200)
            ->assertSee('Pilih Tarif Rute Tersimpan')
            ->assertSee('SINGKOPEK')
            ->assertSee('AMBOYO INTI');

        // 3. Import Proyek (format sheet MASTER PROJECT SENTOSA)
        $projCsv = "NO,NAMA,SUMBER,DEPO,DEBET,SALDO\n"
            . "1,SEKENDAL JAYA,CV Sekendal,50000000,20000000,30000000\n"
            . "2,RUKO KM 6 OWNER,OWNER,30000000,10000000,20000000\n";
        $projFile = \Illuminate\Http\UploadedFile::fake()->createWithContent('master_project.csv', $projCsv);

        $this->post(route('project.projects.import'), ['file' => $projFile])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('projects', ['name' => 'SEKENDAL JAYA', 'budget' => 50000000]);
        $this->assertDatabaseHas('projects', ['name' => 'RUKO KM 6 OWNER', 'customer_id' => null]);

        // 4. Import Karyawan
        $empCsv = "NAMA,ROLE,GAJI HARIAN,NO HP,STATUS\n"
            . "Dimus Karyawan,karyawan,115000,08123456000,aktif\n";
        $empFile = \Illuminate\Http\UploadedFile::fake()->createWithContent('karyawan.csv', $empCsv);

        $this->post(route('hr.employees.import'), ['file' => $empFile])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'name' => 'Dimus Karyawan',
            'role' => 'karyawan',
            'daily_salary' => 115000,
        ]);
    }

    public function test_all_erp_refinements_hpp_pos_bon_po_opname_backup_and_access(): void
    {
        $owner = $this->createStaffUser('owner');
        $kasir = $this->createStaffUser('kasir');

        $this->actingAs($owner);

        $cat = Category::create(['name' => 'Eceran Test', 'slug' => 'eceran-test-' . Str::random(5)]);
        $sup = Supplier::create(['name' => 'Supplier Detail Test', 'is_active' => true]);
        $prod = Product::create([
            'category_id' => $cat->id,
            'supplier_id' => $sup->id,
            'barcode' => 'ECR-' . Str::random(6),
            'name' => 'Semen Eceran Akurat',
            'unit' => 'Sak',
            'secondary_unit' => 'Kg',
            'secondary_ratio' => 40,
            'secondary_price' => 2500,
            'purchase_price' => 50000,
            'selling_price' => 65000,
            'stock_minimum' => 2,
            'is_active' => true,
        ]);

        StockLedger::create([
            'product_id' => $prod->id,
            'user_id' => $owner->id,
            'type' => 'in',
            'qty' => 10,
            'stock_before' => 0,
            'stock_after' => 10,
            'description' => 'Stok Awal',
        ]);

        $cust = Customer::create([
            'name' => 'Pelanggan Bon POS',
            'type' => 'kontraktor',
            'phone' => '081277778888',
            'deposit_balance' => 0,
            'debt_balance' => 0,
            'is_active' => true,
        ]);

        $wallet = Wallet::where('type', 'cash')->first() ?: Wallet::create([
            'name' => 'Kas Laci Test',
            'type' => 'cash',
            'initial_balance' => 0,
            'is_active' => true,
        ]);

        // 1. POS jual 40 Kg (eceran) dengan customer_debt & manual_discount 10.000
        $this->actingAs($kasir);
        CashSession::create([
            'user_id' => $kasir->id,
            'start_time' => now(),
            'opening_balance' => 50000,
            'status' => 'open',
        ]);

        $resBon = $this->postJson(route('pos.store'), [
            'idempotency_key' => (string) Str::uuid(),
            'items' => [['product_id' => $prod->id, 'qty' => 40, 'unit_type' => 'secondary']],
            'payment_method' => 'customer_debt',
            'paid_amount' => 90000,
            'manual_discount' => 10000,
            'discount_note' => 'Diskon kontraktor',
            'customer_id' => $cust->id,
        ]);
        $resBon->assertStatus(200)->assertJson(['status' => 'success']);
        $saleId = (int) basename((string) $resBon->json('redirect'));

        $this->assertEquals(90000, (float) $cust->fresh()->debt_balance);

        // Pastikan HPP eceran pada Laporan Penjualan benar (50.000, bukan 50.000 * 40 = 2.000.000)
        $this->actingAs($owner);
        $reportView = $this->get(route('reports.sales.index', ['category_id' => $cat->id]));
        $reportView->assertStatus(200);
        $summary = $reportView->viewData('summary');
        $this->assertEquals(50000, (float) $summary['total_hpp']);

        $this->get(route('reports.sales.export'))->assertStatus(200);

        // Void struk customer_debt mengembalikan debt_balance & stok
        $this->post(route('pos.void', $saleId), ['reason' => 'Batal bon'])->assertRedirect();
        $this->assertEquals(0, (float) $cust->fresh()->debt_balance);
        $this->assertEquals(10, Product::withStock()->find($prod->id)->current_stock);

        // 2. Pesanan Online: packed -> cancelled mengembalikan stok & kas
        $order = PublicOrder::create([
            'order_code' => 'PUB-REV-' . strtoupper(Str::random(4)),
            'customer_name' => 'Pembeli Batal',
            'phone' => '081200011122',
            'shipping_method' => 'ambil',
            'payment_method' => 'cash',
            'total_amount' => 130000,
            'status' => 'pending',
        ]);
        $order->items()->create([
            'product_id' => $prod->id,
            'qty' => 2,
            'price_snapshot' => 65000,
            'subtotal' => 130000,
        ]);

        $this->post(route('public-orders.status', $order->id), ['status' => 'packed'])->assertRedirect();
        $this->assertEquals(8, Product::withStock()->find($prod->id)->current_stock);

        $this->post(route('public-orders.status', $order->id), ['status' => 'cancelled'])->assertRedirect();
        $this->assertEquals('cancelled', $order->fresh()->status);
        $this->assertEquals(10, Product::withStock()->find($prod->id)->current_stock);
        $this->get(route('public-orders.print', $order->id))->assertStatus(200)->assertSee($order->order_code);

        // 3. Supplier Detail, PO Partial & Cancel, Manual Invoice, Batch Opname, Export
        $this->get(route('master.suppliers.show', $sup->id))->assertStatus(200)->assertSee($sup->name);

        $this->post(route('inventory.po.store'), [
            'po_number' => 'PO-PART-' . Str::random(5),
            'supplier_id' => $sup->id,
            'order_date' => date('Y-m-d'),
            'items' => [['product_id' => $prod->id, 'qty' => 5, 'price' => 50000]],
        ])->assertRedirect(route('inventory.po.index'));

        $po = \App\Models\PurchaseOrder::latest('id')->first();
        $poItem = $po->items->first();
        $this->post(route('inventory.po.receive', $po->id), [
            'received_qtys' => [$poItem->id => 2],
        ])->assertRedirect();
        $this->assertEquals('partial', $po->fresh()->status);
        $this->assertEquals(12, Product::withStock()->find($prod->id)->current_stock);

        $this->get(route('inventory.po.print', $po->id))->assertStatus(200)->assertSee($po->po_number);

        $this->post(route('inventory.invoices.store'), [
            'supplier_id' => $sup->id,
            'invoice_number' => 'INV-MAN-01',
            'total_amount' => 250000,
        ])->assertRedirect();

        $this->post(route('inventory.stock.batch-opname'), [
            'actual_stocks' => [$prod->id => 15],
            'notes' => 'Opname Test',
        ])->assertRedirect();
        $this->assertEquals(15, Product::withStock()->find($prod->id)->current_stock);
        $this->get(route('inventory.stock.export'))->assertStatus(200);

        // 4. DailyReport posSummary, Project Payment & Invoice, Payroll Nonaktif Filter & Debt Status
        $this->getJson(route('daily-reports.pos-summary'))->assertStatus(200)->assertJsonStructure(['uang_kecil', 'uang_besar', 'transfer']);

        $project = Project::create([
            'customer_id' => $cust->id,
            'name' => 'Proyek Termin Test',
            'budget' => 10000000,
            'start_date' => date('Y-m-d'),
            'status' => 'active',
        ]);
        $this->post(route('project.projects.payments.store', $project->id), [
            'amount' => 2500000,
            'payment_date' => date('Y-m-d'),
            'wallet_id' => $wallet->id,
            'notes' => 'DP 1',
        ])->assertRedirect();
        $this->assertEquals(2500000, (float) $project->fresh()->paid_amount);
        $this->get(route('project.projects.invoice', $project->id))->assertStatus(200)->assertSee('Proyek Termin Test');

        // Toggle Nonaktif user & pastikan tidak ikut payroll + terblokir login
        $staffNonaktif = $this->createStaffUser('karyawan');
        $this->post(route('setting.access.toggle', $staffNonaktif->id))->assertRedirect();
        $this->assertEquals('nonaktif', $staffNonaktif->fresh()->employee_status);

        \Illuminate\Support\Facades\Auth::logout();
        $this->post('/login', ['email' => $staffNonaktif->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');

        // Re-login owner & test manual backup + payroll export
        $this->actingAs($owner);
        $this->get(route('hr.payroll.export'))->assertStatus(200);
        $this->get(route('finance.cash-ledger.export'))->assertStatus(200);

        $backupRes = $this->post(route('setting.backup.run'));
        $backupRes->assertRedirect()->assertSessionHas('success');
        // Verifikasi custom notification stack & custom confirm modal aktif di halaman staf dan publik
        $this->followingRedirects()->get(route('setting.backup.index'))
            ->assertStatus(200)
            ->assertSee('id="sb-toast-stack"', false)
            ->assertSee('id="sbConfirmModal"', false);
        $this->get(route('catalog.index'))
            ->assertStatus(200)
            ->assertSee('id="sb-toast-stack"', false);
        // Cleanup any snapshot created during test run so test stays clean
        $latestBackup = collect(glob(storage_path('app/backups/*.sql')))->sortByDesc(fn ($p) => filemtime($p))->first();
        if ($latestBackup) {
            @unlink($latestBackup);
        }
    }
}
