@extends('layouts.public')

@section('title', 'Masuk Akun Pembeli')

@section('content')
<div class="container py-4 py-md-5">
    <div class="row justify-content-center align-items-stretch g-4">
        <!-- Editorial Info Panel -->
        <div class="col-lg-5 d-none d-lg-flex">
            <div class="w-100 rounded-4 p-4 p-xl-5 d-flex flex-column justify-content-between border" style="background-color: var(--sb-surface-muted); border-color: var(--sb-border) !important;">
                <div>
                    <span class="badge bg-white text-dark border px-3 py-2 mb-4">
                        <i class="ph ph-sparkle text-accent me-1"></i> AKUN PELANGGAN SENTOSA
                    </span>
                    <h2 class="fw-bold text-dark mb-3" style="letter-spacing: -0.025em; line-height: 1.2;">
                        Belanja material bangunan lebih cepat &amp; terpantau.
                    </h2>
                    <p class="text-secondary small mb-4" style="line-height: 1.65;">
                        Masuk ke akun pembeli Anda untuk mengakses keranjang tersimpan, riwayat pesanan, dan poin loyalitas.
                    </p>

                    <div class="d-flex flex-column gap-3">
                        <div class="d-flex align-items-start gap-3 p-3 bg-white rounded-3 border">
                            <i class="ph ph-shopping-cart fs-4 text-accent flex-shrink-0"></i>
                            <div>
                                <div class="fw-bold text-dark small">Keranjang Tersinkron Otomatis</div>
                                <div class="text-muted" style="font-size: 0.78rem;">Daftar belanja aman tersimpan di akun Anda.</div>
                            </div>
                        </div>
                        <div class="d-flex align-items-start gap-3 p-3 bg-white rounded-3 border">
                            <i class="ph ph-star fs-4 text-warning flex-shrink-0"></i>
                            <div>
                                <div class="fw-bold text-dark small">Bintang Gold &amp; Saldo Deposit</div>
                                <div class="text-muted" style="font-size: 0.78rem;">Kumpulkan poin belanja dan pantau saldo titipan proyek.</div>
                            </div>
                        </div>
                        <div class="d-flex align-items-start gap-3 p-3 bg-white rounded-3 border">
                            <i class="ph ph-truck fs-4 text-success flex-shrink-0"></i>
                            <div>
                                <div class="fw-bold text-dark small">Lacak Pengiriman Real-Time</div>
                                <div class="text-muted" style="font-size: 0.78rem;">Pantau status dari dikemas hingga armada tiba di lokasi.</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="pt-4 mt-4 border-top small text-muted">
                    Gratis ongkir armada toko untuk belanja minimal <strong>Rp 1.000.000</strong>.
                </div>
            </div>
        </div>

        <!-- Form Card -->
        <div class="col-sm-10 col-md-8 col-lg-5">
            <div class="card h-100">
                <div class="card-body p-4 p-md-5 d-flex flex-column justify-content-center">
                    <div class="mb-4">
                        <div class="d-inline-flex align-items-center justify-content-center rounded-3 border mb-3" style="width: 48px; height: 48px; background-color: var(--sb-accent-soft); border-color: var(--sb-accent-border) !important; color: var(--sb-accent);">
                            <i class="ph ph-shopping-bag fs-4"></i>
                        </div>
                        <h3 class="fw-bold mb-1 text-dark" style="letter-spacing: -0.025em;">Masuk Akun Pembeli</h3>
                        <p class="text-muted small mb-0">Gunakan email dan password akun pembeli Anda.</p>
                    </div>

                    <form method="POST" action="{{ route('customer.login') }}">
                        @csrf
                        <div class="mb-3">
                            <label for="customer_email" class="form-label">Email Pembeli</label>
                            <input type="email"
                                   class="form-control @error('email') is-invalid @enderror"
                                   id="customer_email"
                                   name="email"
                                   value="{{ old('email') }}"
                                   placeholder="email@contoh.com"
                                   required autofocus>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3" x-data="{ showPwd: false }">
                            <label for="customer_password" class="form-label">Password</label>
                            <div class="input-group">
                                <input :type="showPwd ? 'text' : 'password'"
                                       class="form-control @error('password') is-invalid @enderror"
                                       id="customer_password"
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

                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div class="form-check mb-0">
                                <input type="checkbox" class="form-check-input" id="customer_remember" name="remember">
                                <label class="form-check-label small text-secondary" for="customer_remember">Ingat saya</label>
                            </div>
                            <a href="{{ route('customer.register') }}" class="small text-decoration-none fw-semibold text-primary">Daftar Pembeli Baru</a>
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg w-100 mb-3">
                            <i class="ph ph-sign-in"></i> Masuk ke Akun Pembeli
                        </button>
                    </form>

                    <div class="text-center pt-3 border-top">
                        <div class="small text-muted mb-2">Ingin belanja langsung tanpa daftar?</div>
                        <a href="{{ route('catalog.index') }}" class="btn btn-light border btn-sm w-100">
                            <i class="ph ph-storefront text-accent"></i> Lihat Katalog Produk
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
