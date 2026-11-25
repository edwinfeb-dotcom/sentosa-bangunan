@extends('layouts.public')

@section('title', 'Checkout Pemesanan')

@section('body-attrs')
x-data="checkoutApp()"
@endsection

@section('content')
<div class="container py-4 py-md-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-1 text-dark">Checkout Pesanan</h3>
            <p class="text-muted small mb-0">Lengkapi data pengiriman dan pembayaran Anda.</p>
        </div>
        <a href="{{ route('catalog.index') }}" class="btn btn-light border btn-sm">
            <i class="ph ph-arrow-left"></i> Kembali ke Katalog
        </a>
    </div>

    @if(session('error'))
        <div class="alert alert-danger border-0 shadow-sm mb-4">{{ session('error') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger border-0 shadow-sm mb-4">
            <ul class="mb-0 ps-3 small">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('catalog.store-order') }}" method="POST">
        @csrf
        <input type="hidden" name="cart_data" :value="JSON.stringify(cart)">

        <div class="row g-4">
            <!-- Left: Form -->
            <div class="col-lg-7">
                <div class="card">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-3 pb-2 border-bottom">1. Data Pembeli</h5>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label">Nama Lengkap</label>
                                <input type="text" name="customer_name" class="form-control" value="{{ old('customer_name', $user->name ?? '') }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nomor WhatsApp</label>
                                <input type="text" name="phone" class="form-control" value="{{ old('phone', $user->phone ?? $customer->phone ?? '') }}" placeholder="0812xxxxxxxx" required>
                            </div>
                        </div>

                        <h5 class="fw-bold mb-3 pb-2 border-bottom">2. Metode Pengiriman</h5>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="border rounded p-3 d-flex align-items-start gap-2 w-100 cursor-pointer" :class="shippingMethod === 'antar' ? 'border-primary bg-primary bg-opacity-10' : 'bg-light'">
                                    <input class="form-check-input mt-1" type="radio" name="shipping_method" value="antar" x-model="shippingMethod" required>
                                    <div>
                                        <div class="fw-bold"><i class="ph ph-truck me-1"></i> Antar Armada Toko</div>
                                        <small class="text-muted">Rp 25.000 (Gratis min. belanja Rp 1 Jt)</small>
                                    </div>
                                </label>
                            </div>
                            <div class="col-md-6">
                                <label class="border rounded p-3 d-flex align-items-start gap-2 w-100 cursor-pointer" :class="shippingMethod === 'ambil' ? 'border-primary bg-primary bg-opacity-10' : 'bg-light'">
                                    <input class="form-check-input mt-1" type="radio" name="shipping_method" value="ambil" x-model="shippingMethod" required>
                                    <div>
                                        <div class="fw-bold"><i class="ph ph-storefront me-1"></i> Ambil di Toko</div>
                                        <small class="text-muted">Gratis tanpa ongkos kirim</small>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <div class="mb-4" x-show="shippingMethod === 'antar'" x-transition>
                            <label class="form-label">Alamat Pengiriman Lengkap</label>
                            <textarea name="address" class="form-control" rows="3" placeholder="Tulis alamat lengkap beserta patokan lokasi...">{{ old('address', $user->address ?? $customer->address ?? '') }}</textarea>
                        </div>

                        <h5 class="fw-bold mb-3 pb-2 border-bottom">3. Metode Pembayaran</h5>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="border rounded p-3 d-flex align-items-center gap-2 w-100 cursor-pointer" :class="paymentMethod === 'cash' ? 'border-primary bg-primary bg-opacity-10' : 'bg-light'">
                                    <input class="form-check-input mt-0" type="radio" name="payment_method" value="cash" x-model="paymentMethod" required>
                                    <div>
                                        <div class="fw-bold">Cash / COD</div>
                                        <small class="text-muted">Bayar di tempat saat barang tiba</small>
                                    </div>
                                </label>
                            </div>
                            <div class="col-md-6">
                                <label class="border rounded p-3 d-flex align-items-center gap-2 w-100 cursor-pointer" :class="paymentMethod === 'transfer' ? 'border-primary bg-primary bg-opacity-10' : 'bg-light'">
                                    <input class="form-check-input mt-0" type="radio" name="payment_method" value="transfer" x-model="paymentMethod" required>
                                    <div>
                                        <div class="fw-bold">Transfer Bank</div>
                                        <small class="text-muted">BCA Sentosa Bangunan</small>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <div>
                            <label class="form-label">Catatan Pesanan (Opsional)</label>
                            <input type="text" name="notes" class="form-control" value="{{ old('notes') }}" placeholder="Contoh: Kirim pagi sebelum jam 11">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right: Summary -->
            <div class="col-lg-5">
                <div class="card sticky-top" style="top: 90px;">
                    <div class="card-header">Ringkasan Keranjang</div>
                    <div class="card-body p-4">
                        <template x-for="(item, index) in cart" :key="item.id">
                            <div class="d-flex justify-content-between align-items-center pb-2 mb-2 border-bottom">
                                <div>
                                    <div class="fw-semibold small text-dark" x-text="item.name"></div>
                                    <div class="text-muted" style="font-size: 0.78rem;" x-text="item.qty + ' x Rp ' + formatRupiah(item.price)"></div>
                                </div>
                                <div class="fw-bold small" x-text="'Rp ' + formatRupiah(item.price * item.qty)"></div>
                            </div>
                        </template>

                        <div x-show="cart.length === 0" class="text-center py-4 text-muted">
                            Keranjang kosong. <a href="{{ route('catalog.index') }}">Belanja dulu</a>.
                        </div>

                        <div class="mt-3 pt-2">
                            <div class="d-flex justify-content-between mb-2 small text-muted">
                                <span>Subtotal Produk</span>
                                <span x-text="'Rp ' + formatRupiah(subtotal)"></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2 small text-muted">
                                <span>Ongkos Kirim</span>
                                <span :class="shippingCost === 0 ? 'text-success fw-bold' : ''" x-text="shippingCost === 0 ? 'Gratis' : 'Rp ' + formatRupiah(shippingCost)"></span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <span class="fw-bold fs-6">Total Pembayaran</span>
                                <span class="fw-bold fs-4 text-primary" x-text="'Rp ' + formatRupiah(grandTotal)"></span>
                            </div>

                            <button type="submit" class="btn btn-primary btn-lg w-100" :disabled="cart.length === 0">
                                <i class="ph ph-check-circle"></i> Buat Pesanan Sekarang
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    function checkoutApp() {
        const dbCart = @json($dbCart);
        let localCart = [];
        try {
            localCart = JSON.parse(localStorage.getItem('public_cart')) || [];
        } catch (e) {
            localCart = [];
        }
        const initialCart = localCart.length > 0 ? localCart : dbCart;

        return {
            cart: initialCart,
            shippingMethod: '{{ old("shipping_method", "antar") }}',
            paymentMethod: '{{ old("payment_method", "cash") }}',
            get subtotal() {
                return this.cart.reduce((total, item) => total + (Number(item.price) * Number(item.qty)), 0);
            },
            get shippingCost() {
                if (this.shippingMethod === 'ambil' || this.subtotal >= 1000000 || this.cart.length === 0) {
                    return 0;
                }
                return 25000;
            },
            get grandTotal() {
                return this.subtotal + this.shippingCost;
            },
            formatRupiah(value) {
                return new Intl.NumberFormat('id-ID').format(value || 0);
            }
        }
    }
</script>
@endpush
