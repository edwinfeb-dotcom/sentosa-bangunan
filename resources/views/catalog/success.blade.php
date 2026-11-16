<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesanan Berhasil - Sentosa Bangunan</title>
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    <style> body { background-color: #F8FAFC; } </style>
</head>
<body>

    <div class="container mt-5 mb-5">
        <div class="row justify-content-center">
            <div class="col-md-6 text-center">
                <div class="card border-0 shadow-sm" style="border-radius: 16px;">
                    <div class="card-body p-5">
                        
                        <!-- LOGO DI SINI -->
                        <div class="mb-4">
                            <img src="{{ asset('images/logo.png') }}" alt="Logo Sentosa" style="height: 60px; object-fit: contain;">
                        </div>

                        <div class="bg-success bg-opacity-10 text-success rounded-circle d-inline-flex align-items-center justify-content-center mb-4" style="width: 100px; height: 100px;">
                            <i class="ph ph-check-circle fs-1" style="font-size: 4rem;"></i>
                        </div>
                        
                        <h3 class="fw-bold mb-3" style="color: #1E293B;">Pesanan Berhasil!</h3>
                        <p class="text-muted mb-4">Terima kasih, <strong>{{ $order->customer_name }}</strong>. Pesanan Anda dengan kode <strong>{{ $order->order_code }}</strong> telah kami terima.</p>

                        @if($order->payment_method === 'transfer')
                            <div class="alert alert-info border-0 text-start mb-4">
                                <h6 class="fw-bold mb-2"><i class="ph ph-bank me-2"></i> Instruksi Pembayaran (Transfer)</h6>
                                <p class="mb-1">Silakan transfer ke rekening berikut:</p>
                                <h5 class="fw-bold text-primary mb-1">BCA: 123-456-7890</h5>
                                <p class="mb-0">Atas Nama: <strong>Sentosa Bangunan</strong></p>
                                <hr>
                                <p class="mb-0">Total Tagihan: <strong class="fs-5 text-danger">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</strong></p>
                                <small class="text-muted d-block mt-2">*Silakan konfirmasi via WhatsApp setelah transfer.</small>
                            </div>
                        @else
                            <div class="alert alert-warning border-0 text-start mb-4">
                                <h6 class="fw-bold mb-2"><i class="ph ph-money me-2"></i> Instruksi Pembayaran (Cash)</h6>
                                <p class="mb-0">Silakan siapkan uang tunai sebesar <strong class="fs-5">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</strong> saat barang diantar atau diambil di toko.</p>
                            </div>
                        @endif

                        <div class="text-start mb-4">
                            <h6 class="fw-bold border-bottom pb-2 mb-2">Rincian Pesanan:</h6>
                            @foreach($order->items as $item)
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-muted">{{ $item->qty }}x {{ $item->product->name }}</span>
                                    <span class="fw-medium">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</span>
                                </div>
                            @endforeach
                        </div>

                        <script>localStorage.removeItem('public_cart');</script>

                        <a href="{{ route('catalog.index') }}" class="btn btn-primary w-100 fw-bold py-2">
                            Kembali ke Katalog
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

</body>
</html>