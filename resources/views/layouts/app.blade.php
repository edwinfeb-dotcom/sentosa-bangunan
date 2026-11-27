<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Sentosa Bangunan') — Sentosa Bangunan</title>
    <link rel="icon" type="image/png" sizes="64x64" href="{{ asset('images/favicon.png') }}?v=2">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}?v=2">
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    @stack('styles')
</head>
<body x-data="{ sidebarOpen: false }">
    @php $userRole = auth()->user()->role; @endphp

    <div class="app-shell">
        <!-- Mobile Backdrop -->
        <div class="sidebar-backdrop d-print-none" :class="{ 'is-open': sidebarOpen }" @click="sidebarOpen = false"></div>

        <!-- SIDEBAR -->
        <aside class="app-sidebar d-print-none" :class="{ 'is-open': sidebarOpen }">
            <div class="sidebar-brand">
                <a href="{{ route('dashboard') }}" class="d-flex align-items-center text-decoration-none gap-2">
                    <div class="bg-white border rounded-2 d-flex align-items-center justify-content-center flex-shrink-0 p-1" style="width: 40px; height: 40px;">
                        <img src="{{ asset('images/logo.png') }}" alt="Logo Sentosa" style="max-width: 100%; max-height: 100%; object-fit: contain;">
                    </div>
                    <div style="line-height: 1.2;">
                        <div class="fw-bold text-dark" style="font-size: 0.94rem; letter-spacing: -0.02em;">Sentosa Bangunan</div>
                        <div class="text-uppercase fw-semibold" style="font-size: 0.63rem; letter-spacing: 0.08em; color: var(--sb-accent);">
                            {{ $userRole === 'kasir' ? 'Panel Kasir Toko' : 'ERP Manajemen Toko' }}
                        </div>
                    </div>
                </a>
                <button type="button" class="btn btn-sm btn-light border d-lg-none" @click="sidebarOpen = false" aria-label="Tutup Menu">
                    <i class="ph ph-x"></i>
                </button>
            </div>

            <nav class="sidebar-nav">
                @if($userRole === 'kasir')
                    <div class="sidebar-section-label pt-1">Menu Kasir</div>
                    <a href="{{ route('dashboard') }}" class="sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                        <i class="ph ph-house-line"></i> <span>Beranda Kasir</span>
                    </a>
                    <a href="{{ route('pos.session.index') }}" class="sidebar-link {{ request()->routeIs('pos.*') ? 'active' : '' }}">
                        <i class="ph ph-desktop"></i> <span>Kasir (POS)</span>
                    </a>
                    <a href="{{ route('public-orders.index') }}" class="sidebar-link justify-content-between {{ request()->routeIs('public-orders.*') ? 'active' : '' }}">
                        <span class="d-flex align-items-center gap-2">
                            <i class="ph ph-globe"></i> <span>Pesanan Online</span>
                        </span>
                        <span data-pending-orders-badge class="badge bg-warning text-dark rounded-pill {{ ($pendingOnlineOrdersCount ?? 0) > 0 ? '' : 'd-none' }}">{{ $pendingOnlineOrdersCount ?? 0 }}</span>
                    </a>
                    <a href="{{ route('catalog.qr') }}" class="sidebar-link {{ request()->routeIs('catalog.qr') ? 'active' : '' }}">
                        <i class="ph ph-qr-code"></i> <span>QR Web Pelanggan</span>
                    </a>

                    <div class="sidebar-section-label">Produk & Pelanggan</div>
                    <a href="{{ route('master.categories.index') }}" class="sidebar-link {{ request()->routeIs('master.categories.*') ? 'active' : '' }}">
                        <i class="ph ph-tag"></i> <span>Kategori</span>
                    </a>
                    <a href="{{ route('master.products.index') }}" class="sidebar-link {{ request()->routeIs('master.products.*') ? 'active' : '' }}">
                        <i class="ph ph-package"></i> <span>Produk & Barcode</span>
                    </a>
                    <a href="{{ route('master.customers.index') }}" class="sidebar-link {{ request()->routeIs('master.customers.*') ? 'active' : '' }}">
                        <i class="ph ph-users"></i> <span>Pelanggan & Poin</span>
                    </a>

                    <div class="sidebar-section-label">Pembelian & Retur</div>
                    <a href="{{ route('inventory.po.index') }}" class="sidebar-link {{ request()->routeIs('inventory.po.*') ? 'active' : '' }}">
                        <i class="ph ph-shopping-cart"></i> <span>Purchase Order</span>
                    </a>
                    <a href="{{ route('inventory.invoices.index') }}" class="sidebar-link {{ request()->routeIs('inventory.invoices.*') ? 'active' : '' }}">
                        <i class="ph ph-receipt"></i> <span>Nota Supplier (Bon)</span>
                    </a>
                    <a href="{{ route('inventory.returns.index') }}" class="sidebar-link {{ request()->routeIs('inventory.returns.*') ? 'active' : '' }}">
                        <i class="ph ph-arrow-u-up-left"></i> <span>Retur Supplier</span>
                    </a>
                    <a href="{{ route('inventory.stock.index') }}" class="sidebar-link {{ request()->routeIs('inventory.stock.*') ? 'active' : '' }}">
                        <i class="ph ph-stack"></i> <span>Kartu Stok &amp; Opname</span>
                    </a>

                    <div class="sidebar-section-label">Laporan & Kas</div>
                    <a href="{{ route('reports.sales.index') }}" class="sidebar-link {{ request()->routeIs('reports.sales.*') ? 'active' : '' }}">
                        <i class="ph ph-chart-line-up"></i> <span>Laporan Penjualan</span>
                    </a>
                    <a href="{{ route('daily-reports.index') }}" class="sidebar-link {{ request()->routeIs('daily-reports.*') ? 'active' : '' }}">
                        <i class="ph ph-clipboard-text"></i> <span>Laporan Harian</span>
                    </a>
                    <a href="{{ route('finance.cash-ledger.index') }}" class="sidebar-link {{ request()->routeIs('finance.cash-ledger.*') ? 'active' : '' }}">
                        <i class="ph ph-wallet"></i> <span>Buku Kas Shift</span>
                    </a>

                    <div class="sidebar-section-label">Pribadi</div>
                    <a href="{{ route('hr.attendance.index') }}" class="sidebar-link {{ request()->routeIs('hr.attendance.*') ? 'active' : '' }}">
                        <i class="ph ph-qr-code"></i> <span>Absensi &amp; Scan QR</span>
                    </a>
                @elseif($userRole === 'karyawan')
                    <div class="sidebar-section-label pt-1">Menu Karyawan</div>
                    <a href="{{ route('dashboard') }}" class="sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                        <i class="ph ph-house-line"></i> <span>Beranda Karyawan</span>
                    </a>
                    <a href="{{ route('hr.attendance.index') }}" class="sidebar-link {{ request()->routeIs('hr.attendance.*') ? 'active' : '' }}">
                        <i class="ph ph-qr-code"></i> <span>Absensi &amp; Scan QR</span>
                    </a>
                    <a href="{{ route('daily-reports.index') }}" class="sidebar-link {{ request()->routeIs('daily-reports.*') ? 'active' : '' }}">
                        <i class="ph ph-clipboard-text"></i> <span>Laporan Harian</span>
                    </a>
                    <a href="{{ route('catalog.qr') }}" class="sidebar-link {{ request()->routeIs('catalog.qr') ? 'active' : '' }}">
                        <i class="ph ph-storefront"></i> <span>QR Web Pelanggan</span>
                    </a>
                @elseif($userRole === 'gudang')
                    <div class="sidebar-section-label pt-1">Menu Gudang &amp; Stok</div>
                    <a href="{{ route('dashboard') }}" class="sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                        <i class="ph ph-warehouse"></i> <span>Dashboard Gudang</span>
                    </a>
                    <a href="{{ route('public-orders.index') }}" class="sidebar-link justify-content-between {{ request()->routeIs('public-orders.*') ? 'active' : '' }}">
                        <span class="d-flex align-items-center gap-2">
                            <i class="ph ph-globe"></i> <span>Pesanan Online</span>
                        </span>
                        <span data-pending-orders-badge class="badge bg-warning text-dark rounded-pill {{ ($pendingOnlineOrdersCount ?? 0) > 0 ? '' : 'd-none' }}">{{ $pendingOnlineOrdersCount ?? 0 }}</span>
                    </a>
                    <a href="{{ route('inventory.stock.index') }}" class="sidebar-link {{ request()->routeIs('inventory.stock.*') ? 'active' : '' }}">
                        <i class="ph ph-stack"></i> <span>Kartu Stok &amp; Opname</span>
                    </a>
                    <a href="{{ route('inventory.po.index') }}" class="sidebar-link {{ request()->routeIs('inventory.po.*') ? 'active' : '' }}">
                        <i class="ph ph-shopping-cart"></i> <span>Purchase Order</span>
                    </a>
                    <a href="{{ route('inventory.invoices.index') }}" class="sidebar-link {{ request()->routeIs('inventory.invoices.*') ? 'active' : '' }}">
                        <i class="ph ph-receipt"></i> <span>Nota Supplier (Bon)</span>
                    </a>
                    <a href="{{ route('inventory.returns.index') }}" class="sidebar-link {{ request()->routeIs('inventory.returns.*') ? 'active' : '' }}">
                        <i class="ph ph-arrow-u-up-left"></i> <span>Retur Supplier</span>
                    </a>
                    <a href="{{ route('fleet.jobs.index') }}" class="sidebar-link {{ request()->routeIs('fleet.jobs.*') ? 'active' : '' }}">
                        <i class="ph ph-map-pin-line"></i> <span>Jadwal Pengiriman</span>
                    </a>

                    <div class="sidebar-section-label">Pribadi</div>
                    <a href="{{ route('hr.attendance.index') }}" class="sidebar-link {{ request()->routeIs('hr.attendance.*') ? 'active' : '' }}">
                        <i class="ph ph-qr-code"></i> <span>Absensi &amp; Scan QR</span>
                    </a>
                    <a href="{{ route('daily-reports.index') }}" class="sidebar-link {{ request()->routeIs('daily-reports.*') ? 'active' : '' }}">
                        <i class="ph ph-clipboard-text"></i> <span>Laporan Harian</span>
                    </a>
                @elseif($userRole === 'driver')
                    <div class="sidebar-section-label pt-1">Menu Driver &amp; Armada</div>
                    <a href="{{ route('dashboard') }}" class="sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                        <i class="ph ph-truck"></i> <span>Dashboard Driver</span>
                    </a>
                    <a href="{{ route('fleet.jobs.index') }}" class="sidebar-link {{ request()->routeIs('fleet.jobs.*') ? 'active' : '' }}">
                        <i class="ph ph-map-pin-line"></i> <span>Jadwal Pengiriman</span>
                    </a>
                    <a href="{{ route('fleet.vehicles.index') }}" class="sidebar-link {{ request()->routeIs('fleet.vehicles.*') ? 'active' : '' }}">
                        <i class="ph ph-car"></i> <span>Master Armada</span>
                    </a>
                    <a href="{{ route('fleet.assets.index') }}" class="sidebar-link {{ request()->routeIs('fleet.assets.*') ? 'active' : '' }}">
                        <i class="ph ph-wrench"></i> <span>Aset &amp; Peralatan</span>
                    </a>

                    <div class="sidebar-section-label">Pribadi</div>
                    <a href="{{ route('hr.attendance.index') }}" class="sidebar-link {{ request()->routeIs('hr.attendance.*') ? 'active' : '' }}">
                        <i class="ph ph-qr-code"></i> <span>Absensi &amp; Scan QR</span>
                    </a>
                    <a href="{{ route('daily-reports.index') }}" class="sidebar-link {{ request()->routeIs('daily-reports.*') ? 'active' : '' }}">
                        <i class="ph ph-clipboard-text"></i> <span>Laporan Harian</span>
                    </a>
                @else
                    <div class="sidebar-section-label pt-1">Utama &amp; Strategi</div>
                    <a href="{{ route('dashboard') }}" class="sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                        <i class="ph ph-squares-four"></i> <span>Dashboard</span>
                    </a>
                    @if(in_array($userRole, ['owner', 'admin']))
                    <a href="{{ route('setting.targets.index') }}" class="sidebar-link {{ request()->routeIs('setting.targets.*') ? 'active' : '' }}">
                        <i class="ph ph-target"></i> <span>Strategi &amp; Target</span>
                    </a>
                    @endif
                    <a href="{{ route('daily-reports.index') }}" class="sidebar-link {{ request()->routeIs('daily-reports.*') ? 'active' : '' }}">
                        <i class="ph ph-clipboard-text"></i> <span>Laporan Harian</span>
                    </a>
                    <a href="{{ route('reports.sales.index') }}" class="sidebar-link {{ request()->routeIs('reports.sales.*') ? 'active' : '' }}">
                        <i class="ph ph-chart-line-up"></i> <span>Laporan Penjualan</span>
                    </a>

                    @if(in_array($userRole, ['owner', 'admin', 'kasir']))
                    <div class="sidebar-section-label">Kasir & Toko Online</div>
                    <a href="{{ route('pos.session.index') }}" class="sidebar-link {{ request()->routeIs('pos.*') ? 'active' : '' }}">
                        <i class="ph ph-desktop"></i> <span>Kasir (POS)</span>
                    </a>
                    @else
                    <div class="sidebar-section-label">Toko Online</div>
                    @endif
                    <a href="{{ route('public-orders.index') }}" class="sidebar-link justify-content-between {{ request()->routeIs('public-orders.*') ? 'active' : '' }}">
                        <span class="d-flex align-items-center gap-2">
                            <i class="ph ph-globe"></i> <span>Pesanan Online</span>
                        </span>
                        <span data-pending-orders-badge class="badge bg-warning text-dark rounded-pill {{ ($pendingOnlineOrdersCount ?? 0) > 0 ? '' : 'd-none' }}">{{ $pendingOnlineOrdersCount ?? 0 }}</span>
                    </a>
                    <a href="{{ route('catalog.qr') }}" class="sidebar-link {{ request()->routeIs('catalog.qr') ? 'active' : '' }}">
                        <i class="ph ph-qr-code"></i> <span>QR Web Pelanggan</span>
                    </a>

                    <div class="sidebar-section-label">Master Data</div>
                    <a href="{{ route('master.categories.index') }}" class="sidebar-link {{ request()->routeIs('master.categories.*') ? 'active' : '' }}">
                        <i class="ph ph-tag"></i> <span>Kategori</span>
                    </a>
                    <a href="{{ route('master.products.index') }}" class="sidebar-link {{ request()->routeIs('master.products.*') ? 'active' : '' }}">
                        <i class="ph ph-package"></i> <span>Produk</span>
                    </a>
                    @if(in_array($userRole, ['owner', 'admin', 'gudang']))
                    <a href="{{ route('master.suppliers.index') }}" class="sidebar-link {{ request()->routeIs('master.suppliers.*') ? 'active' : '' }}">
                        <i class="ph ph-buildings"></i> <span>Supplier</span>
                    </a>
                    @endif
                    <a href="{{ route('master.customers.index') }}" class="sidebar-link {{ request()->routeIs('master.customers.*') ? 'active' : '' }}">
                        <i class="ph ph-users"></i> <span>Pelanggan</span>
                    </a>

                    <div class="sidebar-section-label">Gudang & Pembelian</div>
                    <a href="{{ route('inventory.po.index') }}" class="sidebar-link {{ request()->routeIs('inventory.po.*') ? 'active' : '' }}">
                        <i class="ph ph-shopping-cart"></i> <span>Purchase Order</span>
                    </a>
                    <a href="{{ route('inventory.invoices.index') }}" class="sidebar-link {{ request()->routeIs('inventory.invoices.*') ? 'active' : '' }}">
                        <i class="ph ph-receipt"></i> <span>Nota Supplier (Bon)</span>
                    </a>
                    <a href="{{ route('inventory.returns.index') }}" class="sidebar-link {{ request()->routeIs('inventory.returns.*') ? 'active' : '' }}">
                        <i class="ph ph-arrow-u-up-left"></i> <span>Retur Supplier</span>
                    </a>
                    <a href="{{ route('inventory.stock.index') }}" class="sidebar-link {{ request()->routeIs('inventory.stock.*') ? 'active' : '' }}">
                        <i class="ph ph-stack"></i> <span>Kartu Stok &amp; Opname</span>
                    </a>

                    <div class="sidebar-section-label">Keuangan</div>
                    @if(in_array($userRole, ['owner', 'admin']))
                    <a href="{{ route('finance.wallets.index') }}" class="sidebar-link {{ request()->routeIs('finance.wallets.*') ? 'active' : '' }}">
                        <i class="ph ph-bank"></i> <span>Dompet & Kas</span>
                    </a>
                    @endif
                    <a href="{{ route('finance.cash-ledger.index') }}" class="sidebar-link {{ request()->routeIs('finance.cash-ledger.*') ? 'active' : '' }}">
                        <i class="ph ph-wallet"></i> <span>Buku Kas</span>
                    </a>
                    @if(in_array($userRole, ['owner', 'admin']))
                    <a href="{{ route('finance.cash-flow.index') }}" class="sidebar-link {{ request()->routeIs('finance.cash-flow.*') ? 'active' : '' }}">
                        <i class="ph ph-arrows-left-right"></i> <span>Laporan Arus Kas</span>
                    </a>
                    @endif

                    <div class="sidebar-section-label">SDM & Payroll</div>
                    <a href="{{ route('hr.attendance.index') }}" class="sidebar-link {{ request()->routeIs('hr.attendance.*') ? 'active' : '' }}">
                        <i class="ph ph-qr-code"></i> <span>Absensi &amp; Scan QR</span>
                    </a>
                    @if(in_array($userRole, ['owner', 'admin']))
                    <a href="{{ route('hr.employees.index') }}" class="sidebar-link {{ request()->routeIs('hr.employees.*') ? 'active' : '' }}">
                        <i class="ph ph-identification-card"></i> <span>Data Karyawan</span>
                    </a>
                    <a href="{{ route('hr.payroll.index') }}" class="sidebar-link {{ request()->routeIs('hr.payroll.*') ? 'active' : '' }}">
                        <i class="ph ph-money"></i> <span>Payroll & Bonus</span>
                    </a>
                    @endif

                    <div class="sidebar-section-label">Armada, Aset & Proyek</div>
                    <a href="{{ route('fleet.vehicles.index') }}" class="sidebar-link {{ request()->routeIs('fleet.vehicles.*') ? 'active' : '' }}">
                        <i class="ph ph-truck"></i> <span>Armada</span>
                    </a>
                    <a href="{{ route('fleet.jobs.index') }}" class="sidebar-link {{ request()->routeIs('fleet.jobs.*') ? 'active' : '' }}">
                        <i class="ph ph-map-pin"></i> <span>Perjalanan Armada</span>
                    </a>
                    <a href="{{ route('fleet.assets.index') }}" class="sidebar-link {{ request()->routeIs('fleet.assets.*') ? 'active' : '' }}">
                        <i class="ph ph-buildings"></i> <span>Register Aset</span>
                    </a>
                    <a href="{{ route('project.projects.index') }}" class="sidebar-link {{ request()->routeIs('project.*') ? 'active' : '' }}">
                        <i class="ph ph-briefcase"></i> <span>Proyek & Material</span>
                    </a>

                    @if(in_array($userRole, ['owner', 'admin']))
                    <div class="sidebar-section-label">Pengaturan</div>
                    <a href="{{ route('setting.store.index') }}" class="sidebar-link {{ request()->routeIs('setting.store.*') ? 'active' : '' }}">
                        <i class="ph ph-storefront"></i> <span>Profil Toko &amp; Rekening</span>
                    </a>
                    <a href="{{ route('setting.checklists.index') }}" class="sidebar-link {{ request()->routeIs('setting.checklists.*') ? 'active' : '' }}">
                        <i class="ph ph-list-checks"></i> <span>Master Form Ceklis</span>
                    </a>
                    @if($userRole === 'owner')
                    <a href="{{ route('setting.access.index') }}" class="sidebar-link {{ request()->routeIs('setting.access.*') ? 'active' : '' }}">
                        <i class="ph ph-shield-check"></i> <span>Akses & Sandi</span>
                    </a>
                    @endif
                    <a href="{{ route('setting.backup.index') }}" class="sidebar-link {{ request()->routeIs('setting.backup.*') ? 'active' : '' }}">
                        <i class="ph ph-cloud-arrow-down"></i> <span>Backup & Export</span>
                    </a>
                    @endif
                @endif
            </nav>

            <div class="sidebar-footer d-none d-lg-flex align-items-center justify-content-between gap-2">
                <div class="d-flex align-items-center gap-2 min-width-0">
                    <div class="text-white rounded-2 d-flex align-items-center justify-content-center fw-bold flex-shrink-0"
                         style="width: 32px; height: 32px; font-size: 0.8rem; background-color: {{ $userRole === 'kasir' ? '#15803d' : '#18181b' }};">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </div>
                    <div class="text-truncate" style="line-height: 1.2;">
                        <div class="fw-semibold text-dark text-truncate" style="font-size: 0.8rem;">{{ auth()->user()->name }}</div>
                        <div class="text-muted text-uppercase" style="font-size: 0.64rem; letter-spacing: 0.06em;">{{ $userRole }}</div>
                    </div>
                </div>
            </div>
        </aside>

        <!-- MAIN WORKSPACE -->
        <div class="app-main">
            <!-- TOPBAR -->
            <header class="app-topbar d-print-none">
                <div class="d-flex align-items-center gap-3">
                    <button type="button" class="btn btn-light border btn-sm d-lg-none" @click="sidebarOpen = true" aria-label="Buka Menu">
                        <i class="ph ph-list fs-5"></i>
                    </button>
                    <h1 class="mb-0 fw-bold text-dark" style="font-size: 1rem; letter-spacing: -0.02em;">@yield('title', 'Sentosa Bangunan')</h1>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-white text-dark border d-none d-md-inline-flex align-items-center gap-1 tabular-nums px-2 py-2" id="topbar-live-clock">
                        <i class="ph ph-clock text-accent"></i>
                        <span>{{ \Carbon\Carbon::now('Asia/Pontianak')->format('H:i:s') }}</span>
                    </span>

                    @if(!request()->routeIs('pos.*') && in_array($userRole, ['owner', 'admin', 'kasir']))
                        <a href="{{ route('pos.session.index') }}" class="btn btn-primary btn-sm d-none d-sm-inline-flex">
                            <i class="ph ph-desktop"></i>
                            <span>Buka POS</span>
                        </a>
                    @endif

                    <a href="{{ route('catalog.index') }}" target="_blank" class="btn btn-light border bg-white btn-sm d-none d-md-inline-flex">
                        <i class="ph ph-storefront text-accent"></i>
                        <span>Katalog</span>
                    </a>

                    <div class="d-flex align-items-center gap-2 ps-2 ms-1 border-start">
                        <div class="text-end d-none d-md-block" style="line-height: 1.2;">
                            <div class="fw-bold text-dark" style="font-size: 0.82rem;">{{ auth()->user()->name }}</div>
                            <span class="badge bg-light text-dark border text-uppercase" style="font-size: 0.62rem;">
                                {{ $userRole === 'kasir' ? 'MODE KASIR' : strtoupper($userRole) }}
                            </span>
                        </div>
                        <form method="POST" action="{{ route('logout') }}" class="mb-0">
                            @csrf
                            <button type="submit" class="btn btn-light border bg-white btn-sm text-danger" title="Keluar / Logout">
                                <i class="ph ph-sign-out"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </header>

            <!-- CONTENT -->
            <main class="container-fluid p-3 p-md-4 flex-grow-1">
                @yield('content')
            </main>
        </div>
    </div>

    @include('partials.sb-notifications')

    <script>
        setInterval(function () {
            const el = document.querySelector('#topbar-live-clock span');
            if (!el) return;
            const now = new Date();
            el.textContent = [now.getHours(), now.getMinutes(), now.getSeconds()]
                .map(n => String(n).padStart(2, '0')).join(':');
        }, 1000);

        document.addEventListener('DOMContentLoaded', function () {
            // Pastikan menu aktif pada sidebar selalu terlihat tanpa perlu scroll manual
            const activeLink = document.querySelector('.sidebar-link.active');
            if (activeLink && typeof activeLink.scrollIntoView === 'function') {
                activeLink.scrollIntoView({ block: 'nearest' });
            }

            // Tutup otomatis notifikasi sukses setelah 5 detik
            document.querySelectorAll('.alert-success.alert-dismissible').forEach(function (alertEl) {
                setTimeout(function () {
                    if (window.bootstrap && window.bootstrap.Alert) {
                        window.bootstrap.Alert.getOrCreateInstance(alertEl).close();
                    }
                }, 5000);
            });

            // Mencegah klik ganda pada form POST standar (non-AJAX)
            document.addEventListener('submit', function (e) {
                if (e.defaultPrevented) return;
                const form = e.target;
                if (!form || form.dataset.noLoading !== undefined) return;
                const method = (form.getAttribute('method') || 'GET').toUpperCase();
                if (method !== 'POST') return;
                const submitBtn = form.querySelector('button[type="submit"]:not([disabled])');
                if (submitBtn) {
                    setTimeout(function () {
                        submitBtn.disabled = true;
                        submitBtn.classList.add('opacity-75');
                    }, 10);
                }
            });

            @if(in_array($userRole, ['owner', 'admin', 'kasir', 'gudang']))
            (function() {
                let lastPendingCount = {{ (int) ($pendingOnlineOrdersCount ?? 0) }};
                const endpoint = "{{ route('public-orders.pending-count') }}";
                const ordersUrl = "{{ route('public-orders.index') }}";

                function updateBadges(count) {
                    document.querySelectorAll('[data-pending-orders-badge]').forEach(el => {
                        el.textContent = count;
                        if (count > 0) {
                            el.classList.remove('d-none');
                        } else {
                            el.classList.add('d-none');
                        }
                    });
                }

                function showOrderToast(count) {
                    if (typeof window.sbToast === 'function') {
                        window.sbToast(`Ada ${count} pesanan online menunggu konfirmasi kasir.`, 'warning', {
                            title: 'Pesanan Online Baru!',
                            actionUrl: ordersUrl,
                            actionLabel: 'Buka Pesanan Online',
                            chime: true,
                            duration: 9000
                        });
                    }
                }

                setInterval(function() {
                    if (document.hidden) return;
                    fetch(endpoint, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
                        .then(r => r.ok ? r.json() : null)
                        .then(data => {
                            if (!data || typeof data.pending_count === 'undefined') return;
                            const count = Number(data.pending_count);
                            updateBadges(count);
                            if (count > lastPendingCount) {
                                showOrderToast(count);
                            }
                            lastPendingCount = count;
                        })
                        .catch(() => {});
                }, 20000);
            })();
            @endif
        });
    </script>
    @stack('scripts')
</body>
</html>
