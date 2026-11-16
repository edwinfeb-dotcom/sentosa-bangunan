<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Sentosa Bangunan</title>
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    <style> body { background-color: #F8FAFC; } </style>
</head>
<body x-data="checkoutApp()">

    <nav class="navbar navbar-light bg-white shadow-sm py-3 mb-5">
        <div class="container d-flex justify-content-between align-items-center">
            <!-- LOGO DI SINI -->
            <a class="navbar-brand" href="{{ route('catalog.index') }}">
                <img src="{{ asset('images/logo.png') }}" alt="Logo Sentosa" style="height: 40px; object-fit: contain;">
            </a>
            <a href="{{ route('catalog.index') }}" class="btn btn-outline-secondary btn-sm fw-bold">
                <i class="ph ph-arrow-left me-1"></i> Kembali ke Katalog
            </a>
        </div>
    </nav>

    <div class="container mb-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card border-0 shadow-sm" style="border-radius: 16px;">
                    <div class="card-body p-4 p-md-5">
                        <h3 class="fw-bold mb-4 text-center" style="color: #1E3A8A;">Formulir Pemesanan</h3>
                        
                        <form action="{{ route('catalog.store-order') }}" method="POST">
                            @csrf
                            <input type="hidden" name="cart_data" :value="JSON.stringify(cart)">
                            
                            <h5 class="fw-bold mb-3 border-bottom pb-2">Data Pembeli</h5>
                            <div class="row mb-4">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-medium">Nama Lengkap</label>
                                    <input type="text" name="customer_name" class="form-control" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-medium">Nomor WhatsApp</label>
                                    <input type="text" name="phone" class="form-control" required>
                                </div>
                            </div>

                            <h5 class="fw-bold mb-3 border-bottom pb-2">Metode Pengiriman</h5>
                            <div class="row mb-4">
                                <div class="col-md-6 mb-3">
                                    <div class="form-check p-3 border rounded bg-light">
                                        <input class="form-check-input" type="radio" name="shipping_method" id="antar" value="antar" x-model="shippingMethod" required>
                                        <label class="form-check-label fw-bold ms-2" for="antar">
                                            <i class="ph ph-truck me-1"></i> Antar ke Alamat
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="form-check p-3 border rounded bg-light">
                                        <input class="form-check-input" type="radio" name="shipping_method" id="ambil" value="ambil" x-model="shippingMethod" required>
                                        <label class="form-check-label fw-bold ms-2" for="ambil">
                                            <i class="ph ph-storefront me-1"></i> Ambil di Tempat
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-4" x-show="shippingMethod === 'antar'" x-transition>
                                <label class="form-label fw-medium">Alamat Pengiriman Lengkap</label>
                                <textarea name="address" class="form-control" rows="3" placeholder="Tulis alamat lengkap beserta patokan..."></textarea>
                            </div>

                            <h5 class="fw-bold mb-3 border-bottom pb-2">Metode Pembayaran</h5>
                            <div class="row mb-4">
                                <div class="col-md-6 mb-3">
                                    <div class="form-check p-3 border rounded bg-light">
                                        <input class="form-check-input" type="radio" name="payment_method" id="cash" value="cash" x-model="paymentMethod" required>
                                        <label class="form-check-label fw-bold ms-2" for="cash">Cash / Bayar di Tempat</label>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="form-check p-3 border rounded bg-light">
                                        <input class="form-check-input" type="radio" name="payment_method" id="transfer" value="transfer" x-model="paymentMethod" required>
                                        <label class="form-check-label fw-bold ms-2" for="transfer">Transfer Bank</label>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-primary bg-opacity-10 rounded p-4 mb-4">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="fw-bold fs-5">Total Tagihan:</span>
                                    <span class="fw-bold fs-3 text-primary" x-text="'Rp ' + formatRupiah(grandTotal)"></span>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary btn-lg w-100 fw-bold" :disabled="cart.length === 0">
                                Beli Sekarang
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function checkoutApp() {
            return {
                cart: JSON.parse(localStorage.getItem('public_cart')) || [],
                shippingMethod: 'antar',
                paymentMethod: 'cash',
                get grandTotal() {
                    return this.cart.reduce((total, item) => total + (item.price * item.qty), 0);
                },
                formatRupiah(value) {
                    return new Intl.NumberFormat('id-ID').format(value);
                }
            }
        }
    </script>
</body>
</html>