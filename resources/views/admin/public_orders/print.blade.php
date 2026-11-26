@extends('layouts.app')

@section('title', 'Nota Pesanan Online')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-body p-4">
                <div class="text-center mb-3">
                    <h5 class="fw-bold mb-1">{{ $storeSetting->store_name ?? 'Sentosa Bangunan' }}</h5>
                    <small class="text-muted d-block">{{ $storeSetting->store_address ?? '' }}</small>
                    @if(!empty($storeSetting->store_phone))
                        <small class="text-muted d-block">Telp/WA: {{ $storeSetting->store_phone }}</small>
                    @endif
                    <hr>
                    <div class="fw-bold">NOTA PESANAN ONLINE</div>
                </div>

                <div class="d-flex justify-content-between small mb-1">
                    <span>Kode Order:</span>
                    <span class="fw-bold font-monospace">{{ $order->order_code }}</span>
                </div>
                <div class="d-flex justify-content-between small mb-1">
                    <span>Pembeli / WA:</span>
                    <span>{{ $order->customer_name }} ({{ $order->phone }})</span>
                </div>
                <div class="d-flex justify-content-between small mb-1">
                    <span>Pengiriman:</span>
                    <span>{{ $order->shipping_method === 'antar' ? 'Antar: ' . $order->address : 'Ambil di Toko' }}</span>
                </div>
                <div class="d-flex justify-content-between small mb-3">
                    <span>Status:</span>
                    <span class="fw-semibold">{{ $order->status_label }}</span>
                </div>

                <hr style="border-style: dashed;">

                @foreach($order->items as $item)
                <div class="mb-2">
                    <div class="fw-medium">{{ $item->product?->name ?? 'Produk' }}</div>
                    <div class="d-flex justify-content-between small text-muted tabular-nums">
                        <span>{{ $item->qty }} x Rp {{ number_format($item->price_snapshot ?? 0, 0, ',', '.') }}</span>
                        <span>Rp {{ number_format($item->subtotal, 0, ',', '.') }}</span>
                    </div>
                </div>
                @endforeach

                @if((float) $order->shipping_cost > 0)
                <div class="d-flex justify-content-between small text-muted mb-1 tabular-nums">
                    <span>Ongkos Kirim:</span>
                    <span>Rp {{ number_format($order->shipping_cost, 0, ',', '.') }}</span>
                </div>
                @endif

                <hr style="border-style: dashed;">

                <div class="d-flex justify-content-between fw-bold fs-5 tabular-nums mb-4">
                    <span>Total Tagihan</span>
                    <span>Rp {{ number_format($order->total_amount, 0, ',', '.') }}</span>
                </div>

                <div class="d-flex gap-2 d-print-none">
                    <a href="{{ route('public-orders.index') }}" class="btn btn-light border w-100">Kembali</a>
                    <button type="button" onclick="window.print()" class="btn btn-primary w-100">
                        <i class="ph ph-printer"></i> Cetak Nota
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
<style>
@media print {
    .app-sidebar, .app-topbar, .d-print-none { display: none !important; }
    .app-main, .page-content { margin: 0 !important; padding: 0 !important; }
    .card { border: none !important; box-shadow: none !important; }
}
</style>
@endsection
