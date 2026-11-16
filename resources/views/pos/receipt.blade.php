@extends('layouts.app')

@section('title', 'Struk Pembayaran')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <div class="text-center mb-4">
                    <!-- LOGO DI SINI -->
                    <img src="{{ asset('images/logo.png') }}" alt="Logo Sentosa" style="height: 50px; object-fit: contain;" class="mb-2">
                    <h5 class="fw-bold mb-0">Sentosa Bangunan</h5>
                    <small class="text-muted">Jl. Raya Pontianak (Contoh Alamat)</small>
                    <hr>
                </div>

                <div class="d-flex justify-content-between mb-1">
                    <span>No. Invoice:</span>
                    <span class="fw-medium">{{ $sale->invoice_number }}</span>
                </div>
                <div class="d-flex justify-content-between mb-1">
                    <span>Tanggal:</span>
                    <span>{{ $sale->created_at->format('d/m/Y H:i') }}</span>
                </div>
                <div class="d-flex justify-content-between mb-3">
                    <span>Kasir:</span>
                    <span>{{ $sale->cashier->name }}</span>
                </div>

                <hr style="border-style: dashed;">

                <!-- Daftar Barang -->
                @foreach($sale->items as $item)
                <div class="mb-2">
                    <div class="fw-medium">{{ $item->product->name }}</div>
                    <div class="d-flex justify-content-between text-muted">
                        <small>{{ $item->qty }} x Rp {{ number_format($item->price_snapshot, 0, ',', '.') }}</small>
                        <small>Rp {{ number_format($item->subtotal, 0, ',', '.') }}</small>
                    </div>
                </div>
                @endforeach

                <hr style="border-style: dashed;">

                <div class="d-flex justify-content-between mb-1">
                    <span class="fw-bold">Total Tagihan:</span>
                    <span class="fw-bold fs-5">Rp {{ number_format($sale->total_amount, 0, ',', '.') }}</span>
                </div>
                <div class="d-flex justify-content-between mb-1">
                    <span>Metode Bayar:</span>
                    <span class="text-capitalize">{{ $sale->payments->first()->method }}</span>
                </div>
                <div class="d-flex justify-content-between mb-3">
                    <span>Uang Diterima:</span>
                    <span>Rp {{ number_format($sale->payments->first()->amount, 0, ',', '.') }}</span>
                </div>

                <div class="text-center mt-4">
                    <p class="mb-1">Terima Kasih!</p>
                    <small class="text-muted">Barang yang sudah dibeli tidak dapat ditukar/dikembalikan.</small>
                </div>

                <div class="d-flex gap-2 mt-4 d-print-none">
                    <a href="{{ route('pos.index') }}" class="btn btn-light border w-100">Kembali ke POS</a>
                    <button onclick="window.print()" class="btn btn-primary w-100">Cetak Struk</button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    @media print {
        body { background-color: white !important; }
        #sidebar-wrapper, .navbar, .d-print-none { display: none !important; }
        #page-content-wrapper { width: 100% !important; margin: 0 !important; padding: 0 !important; }
        .card { border: none !important; box-shadow: none !important; }
        .container-fluid { padding: 0 !important; }
        .col-md-5 { width: 100% !important; max-width: 100% !important; flex: 0 0 100% !important; }
    }
</style>
@endsection