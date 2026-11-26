@extends('layouts.public')

@section('title', 'Akun Pembeli')

@section('content')
@php
    $totalOrdersCount = $orders->total();
    $activeOrdersCount = $orders->getCollection()->whereIn('status', ['pending', 'packed', 'shipped'])->count();
@endphp

<div class="container py-4 py-md-5">
    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm mb-4 d-flex align-items-center gap-2">
            <i class="ph ph-check-circle fs-5"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if($user->role !== 'pelanggan')
        <div class="alert alert-warning border-0 shadow-sm mb-4 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div class="small">
                <i class="ph ph-info me-1"></i>
                Anda sedang masuk menggunakan akun <strong class="text-uppercase">{{ $user->role }}</strong>. Halaman ini adalah tampilan portal untuk akun <strong>Pembeli</strong>.
            </div>
            <a href="{{ route('dashboard') }}" class="btn btn-dark btn-sm">
                <i class="ph ph-squares-four"></i> Kembali ke Dashboard ERP
            </a>
        </div>
    @endif

    <!-- Header Portal Pembeli -->
    <div class="card mb-4">
        <div class="card-body p-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div class="d-flex align-items-center gap-3">
                <div class="bg-dark text-white rounded-3 d-flex align-items-center justify-content-center fw-bold fs-4 flex-shrink-0" style="width: 54px; height: 54px;">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <span class="badge border" style="background-color: var(--sb-accent-soft); border-color: var(--sb-accent-border) !important; color: var(--sb-accent);">PORTAL PEMBELI</span>
                        @if($customer)
                            <span class="badge bg-light text-dark border text-capitalize">{{ $customer->type }}</span>
                        @endif
                    </div>
                    <h4 class="fw-bold text-dark mb-0" style="letter-spacing: -0.02em;">Halo, {{ $user->name }}</h4>
                    <div class="small text-muted">{{ $user->email }} {{ $user->phone ? '• ' . $user->phone : '' }}</div>
                </div>
            </div>

            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('catalog.index') }}" class="btn btn-primary">
                    <i class="ph ph-storefront"></i> Mulai Belanja
                </a>
                <a href="{{ route('catalog.track') }}" class="btn btn-light border">
                    <i class="ph ph-truck"></i> Lacak Pesanan
                </a>
            </div>
        </div>
    </div>

    <!-- Ringkasan Cepat Pembeli -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-label">Total Pesanan</div>
                <div class="stat-value">{{ $totalOrdersCount }} Order</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-label">Sedang Diproses</div>
                <div class="stat-value text-info">{{ $activeOrdersCount }} Order</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-label">Bintang Gold</div>
                <div class="stat-value text-warning">
                    <i class="ph-fill ph-star"></i> {{ $customer->points ?? 0 }}
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-label">Saldo Deposit</div>
                <div class="stat-value text-success">Rp {{ number_format($customer->deposit_balance ?? 0, 0, ',', '.') }}</div>
                @if((float) ($customer->debt_balance ?? 0) > 0)
                    <div class="small text-danger fw-semibold mt-1">Sisa Bon: Rp {{ number_format($customer->debt_balance, 0, ',', '.') }}</div>
                @endif
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Profil & Alamat -->
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header">
                    <i class="ph ph-user-gear text-accent me-2"></i>Profil &amp; Alamat Pengiriman
                </div>
                <div class="card-body p-4">
                    <p class="small text-muted mb-3">
                        Data ini otomatis terisi saat Anda melakukan checkout di Katalog Produk.
                    </p>

                    <form action="{{ route('catalog.account.update') }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="mb-3">
                            <label class="form-label">Nama Lengkap</label>
                            <input type="text" name="name" class="form-control" value="{{ old('name', $user->name) }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nomor WhatsApp</label>
                            <input type="text" name="phone" class="form-control" value="{{ old('phone', $user->phone) }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Alamat Pengiriman Default</label>
                            <textarea name="address" class="form-control" rows="3" placeholder="Tulis alamat lengkap beserta patokan...">{{ old('address', $user->address) }}</textarea>
                        </div>
                        <button type="submit" class="btn btn-dark w-100">
                            <i class="ph ph-floppy-disk"></i> Simpan Profil
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Riwayat Pesanan -->
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="ph ph-receipt text-accent me-2"></i>Riwayat Pesanan Anda</span>
                    <a href="{{ route('catalog.index') }}" class="btn btn-primary btn-sm">
                        <i class="ph ph-plus"></i> Belanja Lagi
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Pesanan &amp; Item</th>
                                    <th>Pengiriman</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($orders as $order)
                                    <tr>
                                        <td>
                                            <div class="fw-bold text-dark font-monospace">{{ $order->order_code }}</div>
                                            <small class="text-muted d-block mb-1">{{ $order->created_at->format('d/m/Y H:i') }}</small>
                                            @if($order->items->isNotEmpty())
                                                <div class="small text-secondary">
                                                    {{ $order->items->take(2)->map(fn($i) => $i->qty . 'x ' . ($i->product->name ?? 'Produk'))->implode(', ') }}
                                                    @if($order->items->count() > 2)
                                                        <span>+{{ $order->items->count() - 2 }} lainnya</span>
                                                    @endif
                                                </div>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border">
                                                {{ $order->shipping_method === 'antar' ? 'Antar Armada' : 'Ambil di Toko' }}
                                            </span>
                                        </td>
                                        <td class="fw-bold">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</td>
                                        <td><span class="badge {{ $order->status_badge_class }}">{{ $order->status_label }}</span></td>
                                        <td class="text-end">
                                            <a href="{{ route('catalog.track', ['order_code' => $order->order_code]) }}" class="btn btn-sm btn-light border">
                                                <i class="ph ph-map-pin text-accent"></i> Lacak
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-5 text-muted">
                                            <i class="ph ph-shopping-bag fs-1 d-block mb-2 opacity-50"></i>
                                            <div class="fw-bold text-dark mb-1">Belum ada riwayat pesanan</div>
                                            <p class="small mb-3">Silakan pilih material bangunan dari katalog untuk mulai memesan.</p>
                                            <a href="{{ route('catalog.index') }}" class="btn btn-primary btn-sm">
                                                <i class="ph ph-storefront"></i> Lihat Katalog Produk
                                            </a>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if($orders->hasPages())
                    <div class="card-footer bg-white">{{ $orders->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
