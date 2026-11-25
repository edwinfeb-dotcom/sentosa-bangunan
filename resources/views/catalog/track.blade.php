@extends('layouts.public')

@section('title', 'Lacak Pesanan')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-body p-4">
                    <h4 class="fw-bold mb-1 text-dark" style="letter-spacing: -0.02em;"><i class="ph ph-truck text-accent me-2"></i>Lacak Status Pesanan</h4>
                    <p class="text-muted small mb-3">Masukkan kode pesanan Anda (contoh: <code>PUB-20261120-XXXX</code>).</p>

                    <form action="{{ route('catalog.track') }}" method="GET" class="row g-2">
                        <div class="col-sm-9">
                            <input type="text" name="order_code" class="form-control form-control-lg" value="{{ $orderCode }}" placeholder="Masukkan Kode Pesanan..." required>
                        </div>
                        <div class="col-sm-3">
                            <button type="submit" class="btn btn-dark btn-lg w-100">Lacak</button>
                        </div>
                    </form>
                </div>
            </div>

            @if($notFound)
                <div class="alert alert-warning border-0 shadow-sm">
                    <i class="ph ph-warning-circle me-1"></i> Pesanan dengan kode <strong>{{ $orderCode }}</strong> tidak ditemukan. Pastikan penulisan kode sudah benar.
                </div>
            @endif

            @if($order)
                @php
                    $steps = [
                        'pending' => ['label' => 'Menunggu Konfirmasi', 'icon' => 'ph-clock'],
                        'packed' => ['label' => 'Dikemas', 'icon' => 'ph-package'],
                        'shipped' => ['label' => 'Dikirim / Siap Diambil', 'icon' => 'ph-truck'],
                        'completed' => ['label' => 'Selesai', 'icon' => 'ph-check-circle'],
                    ];
                    $orderIndex = match($order->status) {
                        'pending' => 0,
                        'packed' => 1,
                        'shipped' => 2,
                        'completed', 'processed' => 3,
                        default => -1,
                    };
                @endphp

                <div class="card border-0 shadow-sm">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>Pesanan <strong class="text-primary">{{ $order->order_code }}</strong></span>
                        <span class="badge {{ $order->status_badge_class }}">{{ $order->status_label }}</span>
                    </div>
                    <div class="card-body p-4">
                        @if($order->status === 'cancelled')
                            <div class="alert alert-danger border-0 mb-4">
                                <i class="ph ph-x-circle me-1"></i> Pesanan ini telah dibatalkan.
                            </div>
                        @else
                            <div class="row g-2 text-center mb-4">
                                @foreach(array_values($steps) as $idx => $step)
                                    <div class="col-3">
                                        <div class="p-2 rounded-3 border {{ $idx <= $orderIndex ? 'bg-primary bg-opacity-10 border-primary text-primary' : 'bg-light text-muted' }}">
                                            <i class="ph {{ $step['icon'] }} fs-4 d-block mb-1"></i>
                                            <div class="fw-bold" style="font-size: 0.72rem;">{{ $step['label'] }}</div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <div class="row g-3 mb-4 small">
                            <div class="col-md-6">
                                <div class="text-muted">Penerima</div>
                                <div class="fw-bold">{{ $order->customer_name }} ({{ $order->phone }})</div>
                            </div>
                            <div class="col-md-6">
                                <div class="text-muted">Metode Pengiriman & Alamat</div>
                                <div class="fw-bold">{{ $order->shipping_method === 'antar' ? 'Antar Armada' : 'Ambil di Toko' }}</div>
                                <div class="text-muted">{{ $order->address }}</div>
                            </div>
                        </div>

                        <h6 class="fw-bold border-bottom pb-2">Daftar Produk</h6>
                        @foreach($order->items as $item)
                            <div class="d-flex justify-content-between py-2 border-bottom small">
                                <span>{{ $item->qty }}x {{ $item->product->name ?? 'Produk' }}</span>
                                <span class="fw-semibold">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</span>
                            </div>
                        @endforeach
                        @if($order->shipping_cost > 0)
                            <div class="d-flex justify-content-between py-2 border-bottom small text-muted">
                                <span>Ongkos Kirim</span>
                                <span>Rp {{ number_format($order->shipping_cost, 0, ',', '.') }}</span>
                            </div>
                        @endif
                        <div class="d-flex justify-content-between pt-3 fw-bold fs-6">
                            <span>Total Tagihan</span>
                            <span class="text-primary">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</span>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
