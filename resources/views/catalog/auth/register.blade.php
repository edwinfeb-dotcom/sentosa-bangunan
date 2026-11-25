@extends('layouts.public')

@section('title', 'Daftar Akun Pembeli')

@section('content')
<div class="container py-4 py-md-5">
    <div class="row justify-content-center align-items-stretch g-4">
        <!-- Editorial Info Panel -->
        <div class="col-lg-5 d-none d-lg-flex">
            <div class="w-100 rounded-4 p-4 p-xl-5 d-flex flex-column justify-content-between border" style="background-color: var(--sb-surface-muted); border-color: var(--sb-border) !important;">
                <div>
                    <span class="badge bg-white text-dark border px-3 py-2 mb-4">
                        <i class="ph ph-user-plus text-accent me-1"></i> PENDAFTARAN GRATIS
                    </span>
                    <h2 class="fw-bold text-dark mb-3" style="letter-spacing: -0.025em; line-height: 1.2;">
                        Buat akun pembeli dalam kurang dari 1 menit.
                    </h2>
                    <p class="text-secondary small mb-4" style="line-height: 1.65;">
                        Cocok untuk pemilik rumah, tukang, maupun kontraktor yang rutin memesan material bangunan di Sentosa Bangunan.
                    </p>

                    <ul class="list-unstyled d-flex flex-column gap-3 small mb-0">
                        <li class="d-flex align-items-center gap-2 text-dark">
                            <i class="ph-fill ph-check-circle text-success fs-5"></i>
                            <span>Checkout lebih cepat dengan alamat tersimpan</span>
                        </li>
                        <li class="d-flex align-items-center gap-2 text-dark">
                            <i class="ph-fill ph-check-circle text-success fs-5"></i>
                            <span>Riwayat pesanan lengkap &amp; status pengiriman</span>
                        </li>
                        <li class="d-flex align-items-center gap-2 text-dark">
                            <i class="ph-fill ph-check-circle text-success fs-5"></i>
                            <span>Poin loyalitas Bintang Gold &amp; saldo deposit</span>
                        </li>
                    </ul>
                </div>

                <div class="pt-4 mt-4 border-top small text-muted">
                    Sudah memiliki akun? <a href="{{ route('customer.login') }}" class="fw-semibold text-primary text-decoration-none">Masuk di sini</a>.
                </div>
            </div>
        </div>

        <!-- Register Form -->
        <div class="col-sm-10 col-md-8 col-lg-6">
            <div class="card h-100">
                <div class="card-body p-4 p-md-5">
                    <h3 class="fw-bold mb-1 text-dark" style="letter-spacing: -0.025em;">Daftar Akun Pembeli</h3>
                    <p class="text-muted small mb-4">Simpan keranjang belanja, lihat riwayat order, dan kumpulkan poin Bintang Gold.</p>

                    @if($errors->any())
                        <div class="alert alert-danger border-0 small">
                            <ul class="mb-0 ps-3">
                                @foreach($errors->all() as $err)
                                    <li>{{ $err }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('customer.register') }}" method="POST">
                        @csrf
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Nama Lengkap</label>
                                <input type="text" name="name" class="form-control" value="{{ old('name') }}" placeholder="Contoh: Budi Santoso" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nomor WhatsApp</label>
                                <input type="text" name="phone" class="form-control" value="{{ old('phone') }}" placeholder="0812xxxxxxxx" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" value="{{ old('email') }}" placeholder="nama@email.com" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Alamat Pengiriman (Opsional)</label>
                            <textarea name="address" class="form-control" rows="2" placeholder="Nama jalan, nomor rumah, dan patokan...">{{ old('address') }}</textarea>
                        </div>

                        <div class="row g-3 mb-4" x-data="{ showPwd: false }">
                            <div class="col-md-6">
                                <label class="form-label">Password</label>
                                <div class="input-group">
                                    <input :type="showPwd ? 'text' : 'password'" name="password" class="form-control" placeholder="Min. 6 karakter" required>
                                    <button type="button" class="btn btn-light border" @click="showPwd = !showPwd" aria-label="Tampilkan password">
                                        <i class="ph" :class="showPwd ? 'ph-eye-slash' : 'ph-eye'"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Konfirmasi Password</label>
                                <input :type="showPwd ? 'text' : 'password'" name="password_confirmation" class="form-control" placeholder="Ulangi password" required>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg w-100 mb-3">
                            <i class="ph ph-user-plus"></i> Buat Akun Sekarang
                        </button>
                        <div class="text-center small text-muted">
                            Sudah punya akun pembeli? <a href="{{ route('customer.login') }}" class="fw-semibold text-primary text-decoration-none">Masuk di sini</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
