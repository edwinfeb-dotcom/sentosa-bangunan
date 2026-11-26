@extends('layouts.public')

@section('title', 'Pesanan Berhasil')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4 p-md-5 text-center">
                    <div class="bg-success bg-opacity-10 text-success rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 84px; height: 84px;">
                        <i class="ph ph-check-circle" style="font-size: 3rem;"></i>
                    </div>

                    <h3 class="fw-bold mb-2 text-dark">Pesanan Berhasil Dibuat!</h3>
                    <p class="text-muted mb-3">Terima kasih, <strong>{{ $order->customer_name }}</strong>. Simpan kode pesanan berikut untuk melacak status pengiriman:</p>

                    <div class="bg-light border rounded-3 p-3 mb-4 d-flex justify-content-between align-items-center">
                        <div class="text-start">
                            <div class="small text-muted">Kode Pesanan</div>
                            <div class="fw-bold fs-5 text-primary font-monospace" id="orderCodeText">{{ $order->order_code }}</div>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="copyOrderCode(this, '{{ $order->order_code }}')">
                            <i class="ph ph-copy"></i> Salin
                        </button>
                    </div>

                    @if($order->payment_method === 'transfer')
                        <div class="alert alert-info border-0 text-start mb-4">
                            <h6 class="fw-bold mb-2"><i class="ph ph-bank me-1"></i> Instruksi Pembayaran Transfer</h6>
                            @if($bankWallets->isNotEmpty())
                                <p class="mb-2 small">Silakan transfer ke salah satu rekening resmi kami:</p>
                                <div class="d-flex flex-column gap-2 mb-2">
                                    @foreach($bankWallets as $bank)
                                        <div class="bg-white border rounded p-2 px-3 d-flex justify-content-between align-items-center gap-2">
                                            <div>
                                                <div class="fw-bold text-dark small">{{ $bank->name }}</div>
                                                <div class="fw-bold text-primary font-monospace fs-6">{{ $bank->account_number }}</div>
                                                <div class="text-muted" style="font-size: 0.75rem;">a.n. <strong>{{ $bank->account_holder ?: $storeSetting->store_name }}</strong></div>
                                            </div>
                                            <button type="button" class="btn btn-sm btn-light border" onclick="copyOrderCode(this, '{{ $bank->account_number }}')">
                                                <i class="ph ph-copy"></i> Salin
                                            </button>
                                        </div>
                                    @endforeach
                                </div>
                                @if($storeSetting->bank_transfer_note)
                                    <div class="small text-muted">{{ $storeSetting->bank_transfer_note }}</div>
                                @endif
                            @else
                                <p class="mb-2 small">
                                    Silakan hubungi kasir / admin <strong>{{ $storeSetting->store_name }}</strong> {{ $storeSetting->store_phone ? '(' . $storeSetting->store_phone . ')' : '' }} untuk konfirmasi nomor rekening tujuan transfer.
                                </p>
                            @endif
                            <hr class="my-2">
                            <div class="d-flex justify-content-between">
                                <span class="small">Total Transfer:</span>
                                <strong class="text-danger tabular-nums">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</strong>
                            </div>
                        </div>
                    @else
                        <div class="alert alert-warning border-0 text-start mb-4">
                            <h6 class="fw-bold mb-1"><i class="ph ph-money me-1"></i> Pembayaran Tunai (Cash)</h6>
                            <p class="mb-0 small">Siapkan uang tunai sebesar <strong>Rp {{ number_format($order->total_amount, 0, ',', '.') }}</strong> saat barang tiba / diambil di toko.</p>
                        </div>
                    @endif

                    <div class="text-start mb-4">
                        <h6 class="fw-bold border-bottom pb-2 mb-2">Rincian Item</h6>
                        @foreach($order->items as $item)
                            <div class="d-flex justify-content-between small mb-1">
                                <span class="text-muted">{{ $item->qty }}x {{ $item->product->name ?? 'Produk' }}</span>
                                <span class="fw-semibold">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</span>
                            </div>
                        @endforeach
                        @if($order->shipping_cost > 0)
                            <div class="d-flex justify-content-between small mb-1">
                                <span class="text-muted">Ongkos Kirim</span>
                                <span class="fw-semibold">Rp {{ number_format($order->shipping_cost, 0, ',', '.') }}</span>
                            </div>
                        @endif
                        <div class="d-flex justify-content-between border-top pt-2 mt-2 fw-bold">
                            <span>Total Tagihan</span>
                            <span class="text-primary">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</span>
                        </div>
                    </div>

                    <div class="d-flex flex-column flex-sm-row gap-2">
                        <a href="{{ route('catalog.track', ['order_code' => $order->order_code]) }}" class="btn btn-primary w-100">
                            <i class="ph ph-map-pin"></i> Lacak Pesanan
                        </a>
                        <a href="{{ route('catalog.index') }}" class="btn btn-light border w-100">
                            Kembali ke Katalog
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    localStorage.removeItem('public_cart');
    function copyOrderCode(btn, code) {
        const done = () => {
            const orig = btn.innerHTML;
            btn.innerHTML = '<i class="ph ph-check"></i> Tersalin!';
            setTimeout(() => { btn.innerHTML = orig; }, 2000);
        };
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(code).then(done).catch(() => fallbackCopy(code, done));
        } else {
            fallbackCopy(code, done);
        }
    }
    function fallbackCopy(text, cb) {
        const ta = document.createElement('textarea');
        ta.value = text;
        ta.style.position = 'fixed';
        ta.style.opacity = '0';
        document.body.appendChild(ta);
        ta.select();
        try {
            document.execCommand('copy');
            cb();
        } catch (e) {}
        document.body.removeChild(ta);
    }
</script>
@endsection
