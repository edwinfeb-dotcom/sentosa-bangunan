@extends('layouts.app')

@section('title', 'Kasir POS')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="fw-bold mb-0" style="color: #1E293B;">Kasir: {{ auth()->user()->name }}</h5>
        <p class="text-muted mb-0" style="font-size: 0.8rem;">Pilih produk atau scan barcode untuk memulai.</p>
    </div>
    <button type="button" class="btn btn-danger btn-sm d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#closeSessionModal">
        <i class="ph ph-lock-key"></i> Tutup Kasir
    </button>
</div>

<div x-data="posApp()" class="row g-3">
    <!-- KIRI: Katalog Produk -->
    <div class="col-lg-7">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body p-3">
                <!-- Search Bar -->
                <div class="position-relative mb-3">
                    <i class="ph ph-magnifying-glass position-absolute text-muted" style="left: 15px; top: 12px; font-size: 1.2rem;"></i>
                    <input type="text" class="form-control form-control-lg ps-5 bg-light border-0" placeholder="Scan Barcode atau Cari Nama Produk..." x-model="search" @keydown.enter.prevent="addFirstResult()" style="font-size: 1rem;">
                </div>
                
                <!-- Grid Produk -->
                <div class="row g-2" style="max-height: 65vh; overflow-y: auto; overflow-x: hidden;">
                    <template x-for="product in filteredProducts" :key="product.id">
                        <div class="col-6 col-md-4">
                            <div class="card h-100 border product-card" @click="addToCart(product)" style="cursor: pointer;">
                                <div class="card-body p-3 d-flex flex-column justify-content-between">
                                    <div>
                                        <h6 class="fw-bold mb-1 text-dark" style="font-size: 0.85rem; line-height: 1.3;" x-text="product.name"></h6>
                                        <small class="text-muted d-block mb-2" style="font-size: 0.75rem;" x-text="'Sisa Stok: ' + product.current_stock + ' ' + product.unit"></small>
                                    </div>
                                    <div class="fw-bold text-primary" style="font-size: 1rem;" x-text="'Rp ' + formatRupiah(product.selling_price)"></div>
                                </div>
                            </div>
                        </div>
                    </template>
                    <div x-show="filteredProducts.length === 0" class="col-12 text-center text-muted py-5">
                        <i class="ph ph-magnifying-glass fs-1 opacity-50 mb-2 d-block"></i>
                        Produk tidak ditemukan.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- KANAN: Keranjang & Pembayaran -->
    <div class="col-lg-5">
        <div class="card h-100 border-0 shadow-sm d-flex flex-column" style="max-height: calc(100vh - 100px);">
            <div class="card-header bg-white border-bottom pt-3 pb-3 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0" style="color: #1E293B;">Keranjang</h5>
                <span class="badge bg-primary rounded-pill" x-text="totalItems + ' Item'"></span>
            </div>
            
            <!-- List Item Keranjang (Scrollable) -->
            <div class="card-body p-0 flex-grow-1" style="overflow-y: auto;">
                <template x-for="(item, index) in cart" :key="index">
                    <div class="d-flex align-items-center p-3 border-bottom">
                        <div class="flex-grow-1">
                            <div class="fw-semibold text-dark" style="font-size: 0.9rem;" x-text="item.name"></div>
                            <div class="text-muted" style="font-size: 0.8rem;" x-text="'Rp ' + formatRupiah(item.price)"></div>
                        </div>
                        
                        <div class="d-flex align-items-center gap-2 mx-3">
                            <button class="btn btn-sm btn-light border py-0 px-2" @click="if(item.qty > 1) { item.qty--; calculateTotal(); }">-</button>
                            <input type="number" class="form-control form-control-sm text-center border-0 bg-transparent px-0" style="width: 40px;" x-model.number="item.qty" min="1" @input="calculateTotal()">
                            <button class="btn btn-sm btn-light border py-0 px-2" @click="item.qty++; calculateTotal();">+</button>
                        </div>
                        
                        <div class="text-end fw-bold" style="width: 80px;" x-text="'Rp ' + formatRupiah(item.price * item.qty)"></div>
                        <button class="btn btn-sm text-danger ms-2" @click="removeFromCart(index)"><i class="ph ph-x"></i></button>
                    </div>
                </template>
                <div x-show="cart.length === 0" class="h-100 d-flex flex-column align-items-center justify-content-center text-muted">
                    <i class="ph ph-shopping-cart fs-1 mb-2 opacity-50"></i>
                    <p class="mb-0">Keranjang masih kosong.</p>
                </div>
            </div>
            
            <!-- Area Pembayaran -->
            <div class="card-footer bg-white border-top p-3">
                
                <!-- FITUR PELANGGAN (LOYALTY) -->
                <div class="mb-2">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-end-0"><i class="ph ph-user-circle"></i></span>
                        <select class="form-select border-start-0 bg-light" x-model="selectedCustomer">
                            <option value="">Pelanggan Umum (Tanpa Poin)</option>
                            @foreach($customers as $cust)
                                <option value="{{ $cust->id }}">
                                    {{ $cust->name }} (Poin: {{ $cust->points }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Fitur Diskon Karyawan -->
                <div class="mb-3">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-end-0"><i class="ph ph-user"></i></span>
                        <select class="form-select border-start-0 bg-light" x-model="selectedEmployee" @change="calculateTotal()">
                            <option value="">Pelanggan Umum (Tanpa Diskon)</option>
                            @foreach($employees as $emp)
                                <option value="{{ $emp->id }}" data-discount="{{ $emp->employee_discount }}">
                                    {{ $emp->name }} (Diskon {{ $emp->employee_discount }}%)
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Rincian Harga -->
                <div class="d-flex justify-content-between mb-1 text-muted" style="font-size: 0.85rem;">
                    <span>Subtotal</span>
                    <span x-text="'Rp ' + formatRupiah(subTotal)"></span>
                </div>
                <div class="d-flex justify-content-between mb-2 text-success fw-medium" style="font-size: 0.85rem;" x-show="discountAmount > 0">
                    <span>Diskon Karyawan</span>
                    <span x-text="'- Rp ' + formatRupiah(discountAmount)"></span>
                </div>
                
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="fw-bold text-dark">Total Tagihan</span>
                    <span class="fs-4 fw-bold text-primary" x-text="'Rp ' + formatRupiah(grandTotal)"></span>
                </div>

                <!-- Metode Pembayaran -->
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <select class="form-select form-select-sm" x-model="paymentMethod">
                            <option value="cash">Tunai (Cash)</option>
                            <option value="transfer">Transfer</option>
                            <option value="employee_debt">Potong Gaji</option>
                        </select>
                    </div>
                    <div class="col-6" x-show="paymentMethod !== 'employee_debt'">
                        <input type="number" class="form-control form-control-sm" placeholder="Uang Diterima" x-model.number="paidAmount" min="0">
                    </div>
                </div>

                <!-- Kembalian -->
                <div class="d-flex justify-content-between mb-3" x-show="paymentMethod !== 'employee_debt'">
                    <span class="text-muted" style="font-size: 0.85rem;">Kembalian</span>
                    <span class="fw-bold" :class="changeAmount < 0 ? 'text-danger' : 'text-success'" x-text="'Rp ' + formatRupiah(changeAmount)"></span>
                </div>

                <!-- Tombol Bayar -->
                <button class="btn btn-primary btn-lg w-100 d-flex justify-content-center align-items-center gap-2" @click="processCheckout()" :disabled="cart.length === 0 || (paymentMethod !== 'employee_debt' && paidAmount < grandTotal) || isProcessing">
                    <i class="ph ph-check-circle fs-5" x-show="!isProcessing"></i>
                    <i class="ph ph-spinner-gap fs-5 ph-spin" x-show="isProcessing"></i>
                    <span x-text="isProcessing ? 'Memproses...' : 'Proses Pembayaran'"></span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tutup Kasir -->
<div class="modal fade" id="closeSessionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold">Tutup Kasir</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('pos.session.close') }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="bg-light p-3 rounded mb-3 border">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Modal Awal:</span>
                            <span class="fw-medium">Rp {{ number_format($session->opening_balance, 0, ',', '.') }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Penjualan Tunai:</span>
                            <span class="fw-medium text-success">+ Rp {{ number_format($cashSales, 0, ',', '.') }}</span>
                        </div>
                        <hr class="my-2">
                        <div class="d-flex justify-content-between">
                            <span class="fw-bold">Uang Seharusnya:</span>
                            <span class="fw-bold text-primary fs-5">Rp {{ number_format($expectedCash, 0, ',', '.') }}</span>
                        </div>
                    </div>
                    
                    <p class="text-muted small">Hitung uang fisik (tunai) di dalam laci kasir saat ini, lalu masukkan angkanya di bawah ini:</p>
                    <div class="mb-3">
                        <label class="form-label fw-medium">Uang Fisik di Laci (Rp)</label>
                        <input type="number" name="actual_closing_balance" class="form-control form-control-lg" required min="0" placeholder="Contoh: {{ $expectedCash }}">
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger">Tutup Kasir & Rekonsiliasi</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('styles')
<style>
    .product-card {
        transition: all 0.2s ease;
        background-color: #fff;
    }
    .product-card:hover {
        border-color: #1E3A8A !important;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(30, 58, 138, 0.1) !important;
    }
    input[type=number]::-webkit-inner-spin-button, 
    input[type=number]::-webkit-outer-spin-button { 
        -webkit-appearance: none; 
        margin: 0; 
    }
    input[type=number] {
        -moz-appearance: textfield;
    }
</style>
@endpush

@push('scripts')
<script>
    function posApp() {
        return {
            products: @json($products),
            search: '',
            cart: [],
            paymentMethod: 'cash',
            paidAmount: 0,
            isProcessing: false,
            idempotencyKey: '{{ $idempotencyKey }}',
            
            // Variabel Diskon & Loyalty
            selectedEmployee: '',
            selectedCustomer: '', // <-- Tambahan untuk Pelanggan
            discountAmount: 0,

            get filteredProducts() {
                if (this.search === '') return this.products;
                return this.products.filter(p => 
                    p.name.toLowerCase().includes(this.search.toLowerCase()) || 
                    (p.barcode && p.barcode.includes(this.search))
                );
            },
            get totalItems() {
                return this.cart.reduce((total, item) => total + item.qty, 0);
            },
            get subTotal() {
                return this.cart.reduce((total, item) => total + (item.price * item.qty), 0);
            },
            get grandTotal() {
                return this.subTotal - this.discountAmount;
            },
            get changeAmount() {
                return this.paidAmount - this.grandTotal;
            },
            addToCart(product) {
                let existingItem = this.cart.find(item => item.id === product.id);
                if (existingItem) {
                    existingItem.qty++;
                } else {
                    this.cart.push({
                        id: product.id,
                        name: product.name,
                        price: product.selling_price,
                        qty: 1
                    });
                }
                this.search = ''; 
                this.calculateTotal();
            },
            addFirstResult() {
                if (this.filteredProducts.length > 0) {
                    this.addToCart(this.filteredProducts[0]);
                }
            },
            removeFromCart(index) {
                this.cart.splice(index, 1);
                this.calculateTotal();
            },
            calculateTotal() {
                if (this.selectedEmployee) {
                    let select = document.querySelector('select[x-model="selectedEmployee"]');
                    let selectedOption = select.options[select.selectedIndex];
                    let discountPercent = parseFloat(selectedOption.getAttribute('data-discount')) || 0;
                    this.discountAmount = this.subTotal * (discountPercent / 100);
                } else {
                    this.discountAmount = 0;
                }
                this.paidAmount = this.grandTotal;
            },
            formatRupiah(value) {
                return new Intl.NumberFormat('id-ID').format(value);
            },
            async processCheckout() {
                if (this.cart.length === 0) return;
                
                if (this.paymentMethod === 'employee_debt' && !this.selectedEmployee) {
                    alert('Silakan pilih karyawan terlebih dahulu untuk Potong Gaji.');
                    return;
                }
                if (this.paymentMethod !== 'employee_debt' && this.paidAmount < this.grandTotal) {
                    alert('Uang pembayaran kurang!');
                    return;
                }

                this.isProcessing = true;
                
                try {
                    const response = await fetch('{{ route("pos.store") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            idempotency_key: this.idempotencyKey,
                            items: this.cart.map(item => ({ product_id: item.id, qty: item.qty })),
                            payment_method: this.paymentMethod,
                            paid_amount: this.paymentMethod === 'employee_debt' ? this.grandTotal : this.paidAmount,
                            employee_id: this.selectedEmployee,
                            customer_id: this.selectedCustomer // <-- Tambahan kirim ID pelanggan
                        })
                    });

                    const data = await response.json();

                    if (data.status === 'success') {
                        window.location.href = data.redirect;
                    } else {
                        alert(data.message);
                        this.isProcessing = false;
                    }
                } catch (error) {
                    alert('Terjadi kesalahan sistem.');
                    this.isProcessing = false;
                }
            }
        }
    }
</script>
@endpush
@endsection