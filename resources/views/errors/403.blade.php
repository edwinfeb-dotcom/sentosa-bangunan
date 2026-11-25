<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Akses Dibatasi (403) — Sentosa Bangunan</title>
    <link rel="icon" type="image/png" sizes="64x64" href="{{ asset('images/favicon.png') }}?v=2">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}?v=2">
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
</head>
<body class="d-flex align-items-center min-vh-100 py-4">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card">
                    <div class="card-body p-4 p-md-5 text-center">
                        <div class="mx-auto mb-3 rounded-3 border d-inline-flex align-items-center justify-content-center" style="width: 64px; height: 64px; background-color: var(--sb-accent-soft); border-color: var(--sb-accent-border) !important; color: var(--sb-accent);">
                            <i class="ph ph-shield-warning fs-1"></i>
                        </div>
                        <h4 class="fw-bold text-dark mb-2" style="letter-spacing: -0.02em;">Akses Dibatasi (403)</h4>
                        <p class="text-muted small mb-4">
                            Halaman ini hanya dapat dibuka oleh akun dengan hak akses <strong>Owner</strong> atau <strong>Admin</strong>.
                        </p>

                        @auth
                        <div class="border rounded-3 p-3 mb-4 small text-start" style="background-color: var(--sb-surface-muted);">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted">Akun aktif saat ini:</span>
                                <span class="fw-bold text-dark">{{ auth()->user()->name }}</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Role saat ini:</span>
                                <span class="badge bg-dark text-white text-uppercase">{{ auth()->user()->role }}</span>
                            </div>
                        </div>
                        @endauth

                        <div class="d-flex flex-column flex-sm-row gap-2">
                            <a href="{{ auth()->check() ? (auth()->user()->role === 'pelanggan' ? route('catalog.index') : route('dashboard')) : route('login') }}" class="btn btn-primary w-100">
                                <i class="ph ph-arrow-left"></i> Kembali ke Beranda
                            </a>
                            @auth
                            <form method="POST" action="{{ route('logout') }}" class="w-100 mb-0">
                                @csrf
                                <button type="submit" class="btn btn-light border w-100">
                                    <i class="ph ph-sign-out"></i> Ganti Akun
                                </button>
                            </form>
                            @endauth
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
