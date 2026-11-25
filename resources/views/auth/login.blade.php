<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Staff &amp; Owner — Sentosa Bangunan</title>
    <link rel="icon" type="image/png" sizes="64x64" href="{{ asset('images/favicon.png') }}?v=2">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}?v=2">
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
</head>
<body>
    <div class="auth-split-shell">
        <!-- Left Architectural Showcase -->
        <aside class="auth-showcase d-none d-lg-flex">
            <div class="position-relative" style="z-index: 2;">
                <div class="d-flex align-items-center gap-3 mb-5">
                    <div class="bg-white rounded-2 d-flex align-items-center justify-content-center p-1" style="width: 44px; height: 44px;">
                        <img src="{{ asset('images/logo.png') }}" alt="Logo Sentosa" style="max-width: 100%; max-height: 100%; object-fit: contain;">
                    </div>
                    <div>
                        <div class="fw-bold text-white" style="font-size: 1.05rem; letter-spacing: -0.02em;">Sentosa Bangunan</div>
                        <div class="text-white-50 text-uppercase" style="font-size: 0.68rem; letter-spacing: 0.1em;">Building Supply ERP</div>
                    </div>
                </div>

                <span class="badge border border-light border-opacity-25 text-white px-3 py-2 mb-4" style="background: rgba(255,255,255,0.06);">
                    <i class="ph ph-shield-check me-1 text-warning"></i> PORTAL INTERNAL TOKO
                </span>

                <h1 class="fw-bold text-white mb-3" style="font-size: 2.35rem; line-height: 1.16; letter-spacing: -0.03em;">
                    Kendali penuh stok, kasir, dan keuangan toko.
                </h1>
                <p class="text-white-50 mb-5" style="max-width: 430px; line-height: 1.6;">
                    Workspace terpadu untuk Owner, Admin, Kasir, Gudang, dan Driver dalam mengelola operasional toko bangunan setiap hari.
                </p>

                <div class="d-flex flex-column gap-3" style="max-width: 410px;">
                    <div class="d-flex align-items-start gap-3 p-3 rounded-3 border border-white border-opacity-10" style="background: rgba(255,255,255,0.04);">
                        <i class="ph ph-desktop fs-4 text-warning flex-shrink-0 mt-1"></i>
                        <div>
                            <div class="fw-semibold text-white small">Terminal Kasir &amp; Shift Real-Time</div>
                            <div class="text-white-50" style="font-size: 0.78rem;">Scan barcode cepat, hitung laci otomatis, dan cetak struk instan.</div>
                        </div>
                    </div>
                    <div class="d-flex align-items-start gap-3 p-3 rounded-3 border border-white border-opacity-10" style="background: rgba(255,255,255,0.04);">
                        <i class="ph ph-package fs-4 text-warning flex-shrink-0 mt-1"></i>
                        <div>
                            <div class="fw-semibold text-white small">Persediaan, PO &amp; Nota Supplier</div>
                            <div class="text-white-50" style="font-size: 0.78rem;">Pantau stok kritis, pembelian barang, hingga pelunasan bon pemasok.</div>
                        </div>
                    </div>
                    <div class="d-flex align-items-start gap-3 p-3 rounded-3 border border-white border-opacity-10" style="background: rgba(255,255,255,0.04);">
                        <i class="ph ph-chart-line-up fs-4 text-warning flex-shrink-0 mt-1"></i>
                        <div>
                            <div class="fw-semibold text-white small">Arus Kas, Absensi QR &amp; Payroll</div>
                            <div class="text-white-50" style="font-size: 0.78rem;">Rekap laporan harian, multi-dompet, dan penggajian otomatis.</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="position-relative text-white-50 small d-flex justify-content-between align-items-center pt-4 border-top border-white border-opacity-10" style="z-index: 2;">
                <span>&copy; {{ date('Y') }} Sentosa Bangunan</span>
                <span>Akses Terbatas Karyawan &amp; Owner</span>
            </div>
        </aside>

        <!-- Right Login Form -->
        <main class="auth-form-pane">
            <div class="auth-form-card">
                <div class="d-flex d-lg-none align-items-center gap-2 mb-4 pb-3 border-bottom">
                    <div class="bg-light border rounded-2 d-flex align-items-center justify-content-center p-1" style="width: 38px; height: 38px;">
                        <img src="{{ asset('images/logo.png') }}" alt="Logo Sentosa" style="max-width: 100%; max-height: 100%; object-fit: contain;">
                    </div>
                    <div>
                        <div class="fw-bold text-dark" style="font-size: 0.95rem;">Sentosa Bangunan</div>
                        <span class="text-uppercase fw-semibold" style="font-size: 0.65rem; letter-spacing: 0.08em; color: var(--sb-accent);">PORTAL INTERNAL TOKO</span>
                    </div>
                </div>

                <div class="mb-4">
                    <span class="badge bg-light text-dark border mb-2 d-none d-lg-inline-block">
                        <i class="ph ph-lock-key me-1 text-accent"></i> PORTAL INTERNAL TOKO
                    </span>
                    <h3 class="fw-bold text-dark mb-1" style="letter-spacing: -0.025em;">Masuk ke Sistem</h3>
                    <p class="text-muted small mb-0">Masuk khusus Owner, Admin, Kasir &amp; Staff toko.</p>
                </div>

                <form method="POST" action="{{ route('login') }}">
                    @csrf
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Staff / Owner</label>
                        <input type="email"
                               class="form-control @error('email') is-invalid @enderror"
                               id="email"
                               name="email"
                               value="{{ old('email') }}"
                               placeholder="Masukkan email akun toko"
                               required autofocus>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3" x-data="{ showPwd: false }">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <input :type="showPwd ? 'text' : 'password'"
                                   class="form-control @error('password') is-invalid @enderror"
                                   id="password"
                                   name="password"
                                   placeholder="••••••••"
                                   required>
                            <button type="button" class="btn btn-light border" @click="showPwd = !showPwd" aria-label="Tampilkan atau sembunyikan password">
                                <i class="ph" :class="showPwd ? 'ph-eye-slash' : 'ph-eye'"></i>
                            </button>
                        </div>
                        @error('password')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-check mb-4">
                        <input type="checkbox" class="form-check-input" id="remember" name="remember">
                        <label class="form-check-label small text-secondary" for="remember">Ingat saya di perangkat ini</label>
                    </div>

                    <button type="submit" class="btn btn-dark btn-lg w-100 mb-3">
                        <i class="ph ph-sign-in"></i> Masuk ke Sistem Toko
                    </button>
                </form>

                <div class="pt-3 border-top">
                    <a href="{{ route('catalog.index') }}" class="btn btn-light border w-100 btn-sm">
                        <i class="ph ph-storefront text-accent"></i> Buka Katalog Publik
                    </a>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
