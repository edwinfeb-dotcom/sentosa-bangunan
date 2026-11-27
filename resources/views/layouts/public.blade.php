<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Katalog Produk') — Sentosa Bangunan</title>
    <link rel="icon" type="image/png" sizes="64x64" href="{{ asset('images/favicon.png') }}?v=2">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}?v=2">
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="d-flex flex-column min-vh-100" @yield('body-attrs')>
    <nav class="navbar navbar-expand-lg public-navbar sticky-top py-2 py-lg-3">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gap-2" href="{{ route('catalog.index') }}">
                <span class="d-inline-flex align-items-center justify-content-center bg-light border rounded-2 p-1" style="width: 40px; height: 40px;">
                    <img src="{{ asset('images/logo.png') }}" alt="Logo Sentosa" style="max-width: 100%; max-height: 100%; object-fit: contain;">
                </span>
                <span style="line-height: 1.15;">
                    <span class="fw-bold text-dark d-block" style="font-size: 1rem; letter-spacing: -0.02em;">Sentosa Bangunan</span>
                    <span class="text-muted d-none d-sm-block" style="font-size: 0.68rem; letter-spacing: 0.04em;">SUPPLIER MATERIAL BANGUNAN</span>
                </span>
            </a>

            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#publicNavbar">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="publicNavbar">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-4 gap-lg-2">
                    <li class="nav-item">
                        <a class="nav-link fw-semibold {{ request()->routeIs('catalog.index') ? 'text-primary' : 'text-dark' }}" href="{{ route('catalog.index') }}">
                            <i class="ph ph-storefront me-1"></i> Katalog Produk
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link fw-semibold {{ request()->routeIs('catalog.track') ? 'text-primary' : 'text-dark' }}" href="{{ route('catalog.track') }}">
                            <i class="ph ph-truck me-1"></i> Lacak Pesanan
                        </a>
                    </li>
                </ul>

                <div class="d-flex flex-wrap align-items-center gap-2">
                    @auth
                        @if(auth()->user()->role === 'pelanggan')
                            <a href="{{ route('catalog.account') }}" class="btn btn-light border bg-white btn-sm {{ request()->routeIs('catalog.account') ? 'border-primary text-primary fw-bold' : '' }}">
                                <i class="ph ph-user-circle text-primary"></i> Akun Saya ({{ auth()->user()->name }})
                            </a>
                        @else
                            <a href="{{ route('dashboard') }}" class="btn btn-light border bg-white btn-sm">
                                <i class="ph ph-squares-four text-primary"></i> Dashboard ERP
                            </a>
                        @endif
                        <form method="POST" action="{{ route('logout') }}" class="mb-0">
                            @csrf
                            <button type="submit" class="btn btn-outline-danger btn-sm">Keluar</button>
                        </form>
                    @else
                        @if($storeSetting->show_customer_accounts ?? true)
                            <a href="{{ route('customer.register') }}" class="btn btn-light border bg-white btn-sm">
                                <i class="ph ph-user-plus"></i> Daftar Pembeli
                            </a>
                            <a href="{{ route('customer.login') }}" class="btn btn-dark btn-sm">
                                <i class="ph ph-sign-in"></i> Masuk Pembeli
                            </a>
                        @endif
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    <main class="flex-grow-1">
        @yield('content')
    </main>

    <footer class="bg-white border-top py-4 mt-5">
        <div class="container d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
            <div class="d-flex align-items-center gap-2">
                <span class="fw-bold text-dark" style="letter-spacing: -0.02em;">Sentosa Bangunan</span>
                <span class="text-muted small d-none d-sm-inline">• Material Konstruksi &amp; Bangunan</span>
            </div>
            <div class="d-flex gap-4 small fw-medium">
                <a href="{{ route('catalog.index') }}" class="text-secondary text-decoration-none">Katalog Produk</a>
                <a href="{{ route('catalog.track') }}" class="text-secondary text-decoration-none">Lacak Pesanan</a>
                <a href="{{ route('customer.login') }}" class="text-secondary text-decoration-none">Akun Pembeli</a>
            </div>
            <div class="text-muted small">&copy; {{ date('Y') }} Sentosa Bangunan.</div>
        </div>
    </footer>

    @include('partials.sb-notifications')
    @stack('scripts')
    <script>
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
    </script>
</body>
</html>
