@extends('layouts.app')

@section('title', 'Struk Pembayaran')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="card">
            <div class="card-body p-4">
                <div class="text-center mb-4">
                    <img src="{{ asset('images/logo.png') }}" alt="Logo Sentosa" style="height: 50px; object-fit: contain;" class="mb-2">
                    <h5 class="fw-bold mb-1">{{ $storeSetting->store_name }}</h5>
                    <small class="text-muted d-block">{{ $storeSetting->store_address }}</small>
                    @if($storeSetting->store_phone)
                        <small class="text-muted d-block">Telp/WA: {{ $storeSetting->store_phone }}</small>
                    @endif
                    @if($sale->status === 'cancelled')
                        <div class="mt-2">
                            <span class="badge bg-danger text-white">STRUK DIBATALKAN / RETUR</span>
                        </div>
                    @endif
                    <hr>
                </div>

                <div class="d-flex justify-content-between mb-1 small">
                    <span>No. Invoice:</span>
                    <span class="fw-semibold font-monospace">{{ $sale->invoice_number }}</span>
                </div>
                <div class="d-flex justify-content-between mb-1 small">
                    <span>Tanggal:</span>
                    <span>{{ $sale->created_at->format('d/m/Y H:i') }}</span>
                </div>
                <div class="d-flex justify-content-between mb-1 small">
                    <span>Kasir:</span>
                    <span>{{ $sale->cashier?->name ?? '-' }}</span>
                </div>
                @if($sale->customer)
                <div class="d-flex justify-content-between mb-3 small">
                    <span>Pelanggan:</span>
                    <span class="fw-semibold">{{ $sale->customer->name }}</span>
                </div>
                @else
                <div class="mb-3"></div>
                @endif

                <hr style="border-style: dashed;">

                <!-- Daftar Barang -->
                @foreach($sale->items as $item)
                <div class="mb-2">
                    <div class="fw-medium">{{ $item->product?->name ?? 'Produk Terhapus' }}</div>
                    <div class="d-flex justify-content-between text-muted tabular-nums">
                        <small>{{ $item->qty }} {{ $item->unit_label ?? $item->product?->unit ?? '' }} x Rp {{ number_format($item->price_snapshot, 0, ',', '.') }}</small>
                        <small>Rp {{ number_format($item->subtotal, 0, ',', '.') }}</small>
                    </div>
                </div>
                @endforeach

                <hr style="border-style: dashed;">

                @if((float) ($sale->discount_amount ?? 0) > 0)
                <div class="d-flex justify-content-between mb-1 small tabular-nums text-danger">
                    <span>Diskon Karyawan:</span>
                    <span>- Rp {{ number_format($sale->discount_amount, 0, ',', '.') }}</span>
                </div>
                @endif
                @if((float) ($sale->manual_discount ?? 0) > 0)
                <div class="d-flex justify-content-between mb-1 small tabular-nums text-danger">
                    <span>Potongan{{ $sale->discount_note ? ' (' . $sale->discount_note . ')' : '' }}:</span>
                    <span>- Rp {{ number_format($sale->manual_discount, 0, ',', '.') }}</span>
                </div>
                @endif

                <div class="d-flex justify-content-between mb-1 tabular-nums">
                    <span class="fw-bold">Total Tagihan:</span>
                    <span class="fw-bold fs-5">Rp {{ number_format($sale->total_amount, 0, ',', '.') }}</span>
                </div>
                @php
                    $methodRaw = $sale->payments->first()?->method ?? '-';
                    $methodLabel = match($methodRaw) {
                        'cash' => 'Tunai (Cash)',
                        'transfer' => 'Transfer Bank',
                        'deposit' => 'Potong Saldo Deposit',
                        'customer_debt' => 'Bon / Piutang Pelanggan',
                        'employee_debt' => 'Potong Gaji Karyawan',
                        default => ucfirst($methodRaw),
                    };
                @endphp
                <div class="d-flex justify-content-between mb-1 small">
                    <span>Metode Bayar:</span>
                    <span class="fw-semibold">{{ $methodLabel }}</span>
                </div>
                <div class="d-flex justify-content-between mb-3 small tabular-nums">
                    <span>Uang Diterima:</span>
                    <span>Rp {{ number_format($sale->payments->first()?->amount ?? 0, 0, ',', '.') }}</span>
                </div>

                <div class="text-center mt-4">
                    <p class="mb-1 fw-semibold">Terima Kasih!</p>
                    <small class="text-muted">Barang yang sudah dibeli tidak dapat ditukar/dikembalikan.</small>
                </div>

                <div class="d-flex gap-2 mt-4 d-print-none">
                    <a href="{{ route('pos.index') }}" class="btn btn-light border w-100">Kembali ke POS</a>
                    <button type="button" onclick="window.print()" class="btn btn-primary w-100">Cetak Struk</button>
                </div>

                @if($sale->status === 'completed' && in_array(auth()->user()->role, ['owner', 'admin', 'kasir']))
                <div class="mt-2 d-print-none">
                    <button type="button" class="btn btn-outline-danger btn-sm w-100" data-bs-toggle="modal" data-bs-target="#voidSaleModal">
                        <i class="ph ph-arrow-u-up-left"></i> Batalkan / Retur Struk Ini
                    </button>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

@if($sale->status === 'completed' && in_array(auth()->user()->role, ['owner', 'admin', 'kasir']))
<div class="modal fade d-print-none" id="voidSaleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Batalkan / Retur Struk {{ $sale->invoice_number }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('pos.void', $sale->id) }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="alert alert-warning border-0 small mb-3">
                        Pembatalan struk akan otomatis <strong>mengembalikan stok barang</strong> dan <strong>mengembalikan saldo kas / deposit</strong> sebesar <strong>Rp {{ number_format($sale->total_amount, 0, ',', '.') }}</strong>.
                    </div>
                    <label class="form-label">Alasan Pembatalan / Retur</label>
                    <input type="text" name="reason" class="form-control" placeholder="Contoh: Pembeli batal / salah input item" required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-danger">Ya, Batalkan &amp; Retur</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

<style>
    @media print {
        body { background-color: white !important; }
        .app-sidebar, .app-topbar, .navbar, .d-print-none { display: none !important; }
        .app-main, .page-content { width: 100% !important; margin: 0 !important; padding: 0 !important; }
        .card { border: none !important; box-shadow: none !important; }
        .col-md-5 { width: 100% !important; max-width: 100% !important; flex: 0 0 100% !important; }
    }
</style>
@endsection
