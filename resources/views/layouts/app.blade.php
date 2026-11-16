<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Sentosa Bangunan')</title>
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
</head>
<body>
    <div class="d-flex" id="wrapper" style="min-height: 100vh;">
        <!-- SIDEBAR -->
        <div id="sidebar-wrapper" style="width: 260px; flex-shrink: 0; background-color: #ffffff; border-right: 1px solid #E2E8F0;">
            <div class="sidebar-heading p-4 fw-bold fs-5 d-flex align-items-center border-bottom">
                <div class="bg-primary text-white rounded p-1 me-2 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                    <i class="ph ph-buildings"></i>
                </div>
                SENTOSA
            </div>
            
            <div class="list-group list-group-flush py-3">
                <a href="{{ route('dashboard') }}" class="list-group-item list-group-item-action border-0 px-4 py-2 {{ request()->routeIs('dashboard') ? 'active text-primary fw-bold' : 'text-secondary' }}">
                    <i class="ph ph-squares-four me-2 fs-5 align-middle"></i> Dashboard
                </a>
                <a href="{{ route('master.categories.index') }}" class="list-group-item list-group-item-action border-0 px-4 py-2 {{ request()->routeIs('master.categories.*') ? 'active text-primary fw-bold' : 'text-secondary' }}">
                    <i class="ph ph-tag me-2 fs-5 align-middle"></i> Kategori
                </a>
                <a href="{{ route('master.products.index') }}" class="list-group-item list-group-item-action border-0 px-4 py-2 {{ request()->routeIs('master.products.*') ? 'active text-primary fw-bold' : 'text-secondary' }}">
                    <i class="ph ph-package me-2 fs-5 align-middle"></i> Produk
                </a>
                <a href="{{ route('master.suppliers.index') }}" class="list-group-item list-group-item-action border-0 px-4 py-2 {{ request()->routeIs('master.suppliers.*') ? 'active text-primary fw-bold' : 'text-secondary' }}">
                    <i class="ph ph-truck me-2 fs-5 align-middle"></i> Supplier
                </a>
                <a href="{{ route('master.customers.index') }}" class="list-group-item list-group-item-action border-0 px-4 py-2 {{ request()->routeIs('master.customers.*') ? 'active text-primary fw-bold' : 'text-secondary' }}">
                    <i class="ph ph-users me-2 fs-5 align-middle"></i> Pelanggan
                </a>
                <a href="{{ route('inventory.po.index') }}" class="list-group-item list-group-item-action border-0 px-4 py-2 {{ request()->routeIs('inventory.*') ? 'active text-primary fw-bold' : 'text-secondary' }}">
                    <i class="ph ph-shopping-cart me-2 fs-5 align-middle"></i> Purchase Order
                </a>
                <a href="{{ route('pos.session.index') }}" class="list-group-item list-group-item-action border-0 px-4 py-2 {{ request()->routeIs('pos.*') ? 'active text-primary fw-bold' : 'text-secondary' }}">
                    <i class="ph ph-desktop me-2 fs-5 align-middle"></i> Kasir (POS)
                </a>
                <a href="{{ route('finance.cash-ledger.index') }}" class="list-group-item list-group-item-action border-0 px-4 py-2 {{ request()->routeIs('finance.*') ? 'active text-primary fw-bold' : 'text-secondary' }}">
                    <i class="ph ph-wallet me-2 fs-5 align-middle"></i> Buku Kas
                </a>
                <a href="{{ route('daily-reports.index') }}" class="list-group-item list-group-item-action border-0 px-4 py-2 {{ request()->routeIs('daily-reports.*') ? 'active text-primary fw-bold' : 'text-secondary' }}">
                    <i class="ph ph-clipboard-text me-2 fs-5 align-middle"></i> Laporan Harian
                </a>
                <a href="{{ route('hr.attendance.index') }}" class="list-group-item list-group-item-action border-0 px-4 py-2 {{ request()->routeIs('hr.*') ? 'active text-primary fw-bold' : 'text-secondary' }}">
                    <i class="ph ph-clock me-2 fs-5 align-middle"></i> Absensi
                </a>

                <a href="{{ route('hr.employees.index') }}" class="list-group-item list-group-item-action border-0 px-4 py-2 {{ request()->routeIs('hr.employees.*') ? 'active text-primary fw-bold' : 'text-secondary' }}">
                    <i class="ph ph-identification-card me-2 fs-5 align-middle"></i> Data Karyawan
                </a>
                <a href="{{ route('hr.payroll.index') }}" class="list-group-item list-group-item-action border-0 px-4 py-2 {{ request()->routeIs('hr.payroll.*') ? 'active text-primary fw-bold' : 'text-secondary' }}">
                    <i class="ph ph-money me-2 fs-5 align-middle"></i> Payroll
                </a>
                <a href="{{ route('fleet.vehicles.index') }}" class="list-group-item list-group-item-action border-0 px-4 py-2 {{ request()->routeIs('fleet.*') ? 'active text-primary fw-bold' : 'text-secondary' }}">
                    <i class="ph ph-truck me-2 fs-5 align-middle"></i> Armada
                </a>

                <a href="{{ route('fleet.jobs.index') }}" class="list-group-item list-group-item-action border-0 px-4 py-2 {{ request()->routeIs('fleet.jobs.*') ? 'active text-primary fw-bold' : 'text-secondary' }}">
                    <i class="ph ph-map-pin me-2 fs-5 align-middle"></i> Perjalanan Armada
                </a>
                <a href="{{ route('project.projects.index') }}" class="list-group-item list-group-item-action border-0 px-4 py-2 {{ request()->routeIs('project.*') ? 'active text-primary fw-bold' : 'text-secondary' }}">
                    <i class="ph ph-briefcase me-2 fs-5 align-middle"></i> Proyek
                </a>

                @if(auth()->user()->role === 'owner')
                <a href="{{ route('setting.access.index') }}" class="list-group-item list-group-item-action border-0 px-4 py-2 {{ request()->routeIs('setting.access.*') ? 'active text-primary fw-bold' : 'text-secondary' }}">
                    <i class="ph ph-shield-check me-2 fs-5 align-middle"></i> Akses & Sandi
                </a>
                @endif

                <a href="{{ route('setting.backup.index') }}" class="list-group-item list-group-item-action border-0 px-4 py-2 {{ request()->routeIs('setting.backup.*') ? 'active text-primary fw-bold' : 'text-secondary' }}">
                    <i class="ph ph-cloud-arrow-down me-2 fs-5 align-middle"></i> Backup & Export
                </a>
            </div>
        </div>

        <!-- PAGE CONTENT -->
        <div id="page-content-wrapper" class="flex-grow-1 d-flex flex-column" style="min-width: 0;">
            
            <!-- TOPBAR -->
            <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom px-4 py-3">
                <div class="container-fluid px-0">
                    <span class="navbar-brand mb-0 h1 fw-bold" style="color: #1E293B; font-size: 1.1rem;">@yield('title')</span>
                    
                    <div class="d-flex align-items-center ms-auto gap-3">
                        <div class="text-end d-none d-md-block">
                            <div class="fw-bold" style="font-size: 0.9rem; color: #1E293B;">{{ auth()->user()->name }}</div>
                            <div class="text-muted" style="font-size: 0.75rem;">{{ ucfirst(auth()->user()->role) }}</div>
                        </div>
                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 38px; height: 38px;">
                            {{ substr(auth()->user()->name, 0, 1) }}
                        </div>
                        <form method="POST" action="{{ route('logout') }}" class="mb-0">
                            @csrf
                            <button type="submit" class="btn btn-light border btn-sm" title="Logout">
                                <i class="ph ph-sign-out"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </nav>

            <!-- MAIN CONTENT -->
            <div class="container-fluid p-4 flex-grow-1">
                @yield('content')
            </div>
            
        </div>
    </div>
    
    @stack('scripts')
</body>
</html>