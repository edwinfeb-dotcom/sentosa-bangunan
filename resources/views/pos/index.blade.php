@extends('layouts.app')

@section('title', 'Kasir POS')

@push('styles')
<style>
    .pos-category-strip {
        display: flex;
        gap: 0.45rem;
        overflow-x: auto;
        padding-bottom: 0.35rem;
        scrollbar-width: thin;
    }
    .pos-cat-btn {
        white-space: nowrap;
        padding: 0.36rem 0.8rem;
        border-radius: 999px;
        font-size: 0.78rem;
        font-weight: 600;
        border: 1px solid var(--sb-border);
        background: var(--sb-surface);
        color: var(--sb-ink-secondary);
        cursor: pointer;
        transition: all 0.14s ease;
    }
    .pos-cat-btn:hover {
        border-color: var(--sb-ink);
        color: var(--sb-ink);
    }
    .pos-cat-btn.active {
        background: var(--sb-ink);
        border-color: var(--sb-ink);
        color: #ffffff;
    }
    .pos-product-card {
        transition: border-color 0.15s ease, transform 0.15s ease, box-shadow 0.15s ease;
        background-color: var(--sb-surface);
        border: 1px solid var(--sb-border) !important;
        cursor: pointer;
        position: relative;
    }
    .pos-product-card:hover:not(.out-of-stock) {
        border-color: var(--sb-accent) !important;
        transform: translateY(-2px);
        box-shadow: 0 8px 18px rgba(24, 24, 27, 0.07) !important;
    }
    .pos-product-card.in-cart {
        border-color: var(--sb-ink) !important;
        background-color: #faf9f6;
    }
    .pos-product-card.out-of-stock {
        opacity: 0.5;
        cursor: not-allowed;
    }
    .pos-total-bar {
        background-color: var(--sb-ink);
        color: #ffffff;
        border-radius: 10px;
        padding: 0.85rem 1rem;
    }
</style>
@endpush

@section('content')
<div class="page-header mb-3">
    <div>
        <h2 class="page-title">Kasir Point of Sale</h2>
        <p class="page-subtitle">Kasir aktif: <strong>{{ auth()->user()->name }}</strong> • Scan barcode (<kbd class="bg-white text-dark border px-1 rounded">F2</kbd>) atau klik produk untuk menambahkan.</p>
    </div>
    <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#closeSessionModal">
        <i class="ph ph-lock-key"></i> Tutup Kasir
    </button>
</div>

<div x-data="posApp()" @keydown.window.f2.prevent="$refs.posSearch.focus()" class="row g-3">
    <!-- KIRI: Katalog Produk -->
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-body p-3">
                <div class="input-group mb-2">
                    <span class="input-group-text bg-light border-end-0"><i class="ph ph-magnifying-glass text-muted"></i></span>
                    <input type="text" x-ref="posSearch" class="form-control border-start-0 border-end-0" placeholder="Scan Barcode atau Cari Nama Produk (Enter)..." x-model="search" @input="displayLimit = 24" @keydown.enter.prevent="addFirstResult()">
                    <span class="input-group-text bg-light border-start-0"><kbd class="badge bg-white text-muted border">F2</kbd></span>
                </div>

                <!-- Quick Category Filter Pills -->
                <div class="pos-category-strip mb-3">
                    <button type="button" class="pos-cat-btn" :class="selectedCategory === '' ? 'active' : ''" @click="selectedCategory = ''; displayLimit = 24">
                        Semua
                    </button>
                    @foreach($categories as $cat)
                        <button type="button" class="pos-cat-btn" :class="String(selectedCategory) === '{{ $cat->id }}' ? 'active' : ''" @click="selectedCategory = '{{ $cat->id }}'; displayLimit = 24">
                            {{ $cat->name }}
                        </button>
                    @endforeach
                </div>

                <div class="row g-2" style="max-height: 62vh; overflow-y: auto; overflow-x: hidden;" @scroll="handleGridScroll($event)">
                    <template x-for="product in visibleProducts" :key="product.id">
                        <div class="col-6 col-sm-4 col-md-4">
                            <div class="card h-100 pos-product-card"
                                 :class="{ 'out-of-stock': product.current_stock <= 0, 'in-cart': getCartQty(product.id) > 0 }"
                                 @click="addToCart(product)">
                                <span x-show="getCartQty(product.id) > 0"
                                      class="position-absolute top-0 start-0 m-2 badge bg-dark text-white tabular-nums"
                                      style="z-index: 2;"
                                      x-text="'x' + getCartQty(product.id)"></span>
                                <div class="card-body p-2 d-flex flex-column text-center">
                                    <template x-if="product.image">
                                        <img :src="'/storage/' + product.image" alt="Produk" class="img-fluid rounded mb-2 w-100" style="height: 76px; object-fit: cover;" loading="lazy" decoding="async">
                                    </template>
                                    <template x-if="!product.image">
                                        <div class="rounded mb-2 d-flex align-items-center justify-content-center w-100" style="height: 76px; background-color: var(--sb-canvas);">
                                            <i class="ph ph-package text-muted fs-2 opacity-50"></i>
                                        </div>
                                    </template>

                                    <div class="flex-grow-1 d-flex flex-column justify-content-between">
                                        <h6 class="fw-bold mb-1 text-dark" style="font-size: 0.8rem; line-height: 1.25;" x-text="product.name"></h6>
                                        <div class="mb-1">
                                            <span class="badge tabular-nums"
                                                  :class="product.current_stock <= 0 ? 'bg-danger bg-opacity-10 text-danger' : 'bg-light text-secondary border'"
                                                  x-text="'Stok: ' + product.current_stock + ' ' + product.unit"></span>
                                        </div>
                                        <div class="fw-bold text-accent tabular-nums" style="font-size: 0.88rem;" x-text="'Rp ' + formatRupiah(product.selling_price)"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>

                    <div x-show="filteredProducts.length > displayLimit" class="col-12 text-center py-2">
                        <button type="button" class="btn btn-sm btn-outline-primary" @click="displayLimit += 24">
                            Muat Lebih Banyak (<span x-text="filteredProducts.length - displayLimit"></span> lagi)
                        </button>
                    </div>

                    <div x-show="filteredProducts.length === 0" class="col-12 text-center text-muted py-5">
                        <i class="ph ph-magnifying-glass fs-1 opacity-50 mb-2 d-block"></i>
                        Produk tidak ditemukan.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- KANAN: Terminal Keranjang & Pembayaran -->
    <div class="col-lg-5">
        <div class="card h-100 d-flex flex-column">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="ph ph-shopping-cart me-2 text-accent"></i>Keranjang Transaksi</span>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" x-show="cart.length > 0" @click="clearCart()" class="btn btn-sm btn-light border text-danger py-0 px-2" style="font-size: 0.72rem;" title="Kosongkan Keranjang">
                        <i class="ph ph-trash"></i> Kosongkan
                    </button>
                    <span class="badge bg-dark text-white rounded-pill tabular-nums" x-text="totalItems + ' Item'"></span>
                </div>
            </div>
            <div x-show="noticeText" x-transition class="px-3 py-2 small fw-semibold border-bottom"
                 :class="noticeType === 'error' ? 'bg-danger bg-opacity-10 text-danger' : 'bg-success bg-opacity-10 text-success'"
                 x-text="noticeText"></div>

            <div class="card-body p-0 flex-grow-1" style="max-height: 290px; overflow-y: auto;">
                <template x-for="(item, index) in cart" :key="item.id">
                    <div class="d-flex align-items-center p-3 border-bottom gap-2">
                        <div class="flex-grow-1">
                            <div class="fw-semibold text-dark small" x-text="item.name"></div>
                            <div class="text-muted tabular-nums" style="font-size: 0.76rem;" x-text="'Rp ' + formatRupiah(item.price) + ' (Stok: ' + item.stock + ')'"></div>
                        </div>

                        <div class="d-flex align-items-center gap-1">
                            <button type="button" class="btn btn-sm btn-light border px-2" @click="updateQty(index, item.qty - 1)">-</button>
                            <input type="number" class="form-control form-control-sm text-center px-1 tabular-nums" style="width: 48px;" x-model.number="item.qty" min="1" @change="updateQty(index, item.qty)">
                            <button type="button" class="btn btn-sm btn-light border px-2" @click="updateQty(index, item.qty + 1)">+</button>
                        </div>

                        <div class="text-end fw-bold small tabular-nums" style="min-width: 82px;" x-text="'Rp ' + formatRupiah(item.price * item.qty)"></div>
                        <button type="button" class="btn btn-sm text-danger p-1" @click="removeFromCart(index)"><i class="ph ph-trash"></i></button>
                    </div>
                </template>
                <div x-show="cart.length === 0" class="py-5 text-center text-muted">
                    <i class="ph ph-shopping-cart fs-1 mb-2 opacity-25 d-block"></i>
                    <p class="mb-0 small">Belum ada produk dipilih.</p>
                </div>
            </div>

            <div class="card-footer bg-white border-top p-3">
                <div class="row g-2 mb-2">
                    <div class="col-md-6">
                        <label class="form-label small text-muted mb-1">Pelanggan (Poin &amp; Deposit)</label>
                        <select class="form-select form-select-sm" x-model="selectedCustomer">
                            <option value="">Pelanggan Umum</option>
                            @foreach($customers as $cust)
                                <option value="{{ $cust->id }}" data-deposit="{{ (float) $cust->deposit_balance }}">
                                    {{ $cust->name }} ({{ $cust->points }} ★{{ $cust->deposit_balance > 0 ? ' • Dep: Rp ' . number_format($cust->deposit_balance, 0, ',', '.') : '' }})
                                </option>
                            @endforeach
                        </select>
                        <div class="small text-success fw-semibold mt-1 tabular-nums" x-show="selectedCustomerDeposit > 0">
                            Saldo Deposit: Rp <span x-text="formatRupiah(selectedCustomerDeposit)"></span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small text-muted mb-1">Diskon / Karyawan</label>
                        <select class="form-select form-select-sm" x-model="selectedEmployee" @change="calculateTotal()">
                            <option value="">Tanpa Diskon</option>
                            @foreach($employees as $emp)
                                <option value="{{ $emp->id }}" data-discount="{{ $emp->employee_discount }}">
                                    {{ $emp->name }} ({{ $emp->employee_discount }}%)
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- High-Contrast Total Readout -->
                <div class="pos-total-bar mb-3">
                    <div class="d-flex justify-content-between small text-white-50 tabular-nums">
                        <span>Subtotal</span>
                        <span x-text="'Rp ' + formatRupiah(subTotal)"></span>
                    </div>
                    <div class="d-flex justify-content-between small text-warning fw-semibold tabular-nums" x-show="discountAmount > 0">
                        <span>Diskon Karyawan</span>
                        <span x-text="'- Rp ' + formatRupiah(discountAmount)"></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-1 pt-2 border-top border-white border-opacity-10">
                        <span class="fw-semibold small text-uppercase" style="letter-spacing: 0.06em;">Total Tagihan</span>
                        <span class="fs-4 fw-bold text-white tabular-nums" x-text="'Rp ' + formatRupiah(grandTotal)"></span>
                    </div>
                </div>

                <div class="row g-2 mb-2">
                    <div class="col-6">
                        <label class="form-label small text-muted mb-1">Metode Bayar</label>
                        <select class="form-select form-select-sm" x-model="paymentMethod">
                            <option value="cash">Tunai (Cash)</option>
                            <option value="transfer">Transfer Bank</option>
                            <option value="deposit">Potong Saldo Deposit</option>
                            <option value="employee_debt">Potong Gaji</option>
                        </select>
                    </div>
                    <div class="col-6" x-show="paymentMethod !== 'employee_debt' && paymentMethod !== 'deposit'">
                        <label class="form-label small text-muted mb-1">Masuk ke Dompet</label>
                        <select class="form-select form-select-sm" x-model="walletId">
                            <option value="">Otomatis</option>
                            @foreach($wallets as $wallet)
                                <option value="{{ $wallet->id }}">{{ $wallet->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div x-show="paymentMethod !== 'employee_debt' && paymentMethod !== 'deposit'" class="mb-2">
                    <div class="input-group input-group-sm mb-2">
                        <span class="input-group-text">Uang Diterima</span>
                        <input type="number" class="form-control fw-bold tabular-nums" x-model.number="paidAmount" @input="paidManually = true" min="0">
                    </div>
                    <div class="d-flex flex-wrap gap-1 mb-2">
                        <button type="button" class="btn btn-sm btn-light border" @click="paidManually = false; paidAmount = grandTotal">Uang Pas</button>
                        <button type="button" class="btn btn-sm btn-light border" @click="paidManually = true; paidAmount = 50000">50rb</button>
                        <button type="button" class="btn btn-sm btn-light border" @click="paidManually = true; paidAmount = 100000">100rb</button>
                        <button type="button" class="btn btn-sm btn-light border" @click="paidManually = true; paidAmount = 200000">200rb</button>
                    </div>
                    <div class="d-flex justify-content-between small tabular-nums">
                        <span class="text-muted">Kembalian</span>
                        <span class="fw-bold" :class="changeAmount < 0 ? 'text-danger' : 'text-success'" x-text="'Rp ' + formatRupiah(changeAmount)"></span>
                    </div>
                </div>

                <button type="button" class="btn btn-primary btn-lg w-100 mt-2"
                        @click="processCheckout()"
                        :disabled="cart.length === 0 || (!['employee_debt', 'deposit'].includes(paymentMethod) && paidAmount < grandTotal) || isProcessing">
                    <i class="ph ph-check-circle" x-show="!isProcessing"></i>
                    <span x-text="isProcessing ? 'Memproses...' : 'Proses & Cetak Struk'"></span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tutup Kasir -->
<div class="modal fade" id="closeSessionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Tutup Sesi Kasir</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('pos.session.close') }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="p-3 rounded-3 mb-3 border" style="background-color: var(--sb-surface-muted);">
                        <div class="d-flex justify-content-between mb-1 small tabular-nums">
                            <span class="text-muted">Modal Awal:</span>
                            <span class="fw-semibold">Rp {{ number_format($session->opening_balance, 0, ',', '.') }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-1 small tabular-nums">
                            <span class="text-muted">Penjualan Tunai:</span>
                            <span class="fw-semibold text-success">+ Rp {{ number_format($cashSales, 0, ',', '.') }}</span>
                        </div>
                        <hr class="my-2">
                        <div class="d-flex justify-content-between tabular-nums">
                            <span class="fw-bold">Uang Seharusnya di Laci:</span>
                            <span class="fw-bold text-dark fs-5">Rp {{ number_format($expectedCash, 0, ',', '.') }}</span>
                        </div>
                    </div>

                    <label class="form-label">Uang Fisik Terhitung di Laci (Rp)</label>
                    <input type="number" name="actual_closing_balance" class="form-control form-control-lg tabular-nums" required min="0" value="{{ $expectedCash }}">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger">Tutup Kasir &amp; Rekonsiliasi</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function posApp() {
        return {
            products: @json($products),
            search: '',
            selectedCategory: '',
            displayLimit: 24,
            cart: [],
            paymentMethod: 'cash',
            walletId: '',
            paidAmount: 0,
            paidManually: false,
            isProcessing: false,
            idempotencyKey: '{{ $idempotencyKey }}',
            customersList: @json($customers),
            selectedEmployee: '',
            selectedCustomer: '',
            discountAmount: 0,
            noticeText: '',
            noticeType: 'success',
            noticeTimer: null,

            get selectedCustomerDeposit() {
                if (!this.selectedCustomer) return 0;
                const found = this.customersList.find(c => String(c.id) === String(this.selectedCustomer));
                return found ? Number(found.deposit_balance || 0) : 0;
            },

            init() {
                this.$nextTick(() => {
                    this.$refs.posSearch?.focus();
                });
            },
            getCartQty(productId) {
                const found = this.cart.find(item => item.id === productId);
                return found ? found.qty : 0;
            },
            notifyPos(msg, type = 'success') {
                this.noticeText = msg;
                this.noticeType = type;
                if (this.noticeTimer) clearTimeout(this.noticeTimer);
                this.noticeTimer = setTimeout(() => { this.noticeText = ''; }, 2600);
            },
            clearCart() {
                this.cart = [];
                this.calculateTotal();
                this.notifyPos('Keranjang dikosongkan.', 'success');
            },

            get filteredProducts() {
                return this.products.filter(p => {
                    const matchCat = !this.selectedCategory || String(p.category_id) === String(this.selectedCategory);
                    const q = this.search.trim().toLowerCase();
                    const matchSearch = !q || p.name.toLowerCase().includes(q) || (p.barcode && p.barcode.toLowerCase().includes(q));
                    return matchCat && matchSearch;
                });
            },
            get visibleProducts() {
                return this.filteredProducts.slice(0, this.displayLimit);
            },
            handleGridScroll(event) {
                const el = event.target;
                if (el.scrollTop + el.clientHeight >= el.scrollHeight - 80 && this.filteredProducts.length > this.displayLimit) {
                    this.displayLimit += 24;
                }
            },
            get totalItems() {
                return this.cart.reduce((total, item) => total + Number(item.qty || 0), 0);
            },
            get subTotal() {
                return this.cart.reduce((total, item) => total + (Number(item.price) * Number(item.qty || 0)), 0);
            },
            get grandTotal() {
                return Math.max(0, this.subTotal - this.discountAmount);
            },
            get changeAmount() {
                return (Number(this.paidAmount) || 0) - this.grandTotal;
            },
            addToCart(product) {
                if (product.current_stock <= 0) {
                    this.notifyPos('Stok ' + product.name + ' sedang kosong (0).', 'error');
                    return;
                }
                const existing = this.cart.find(item => item.id === product.id);
                if (existing) {
                    if (existing.qty >= product.current_stock) {
                        this.notifyPos('Jumlah melebihi stok tersedia (' + product.current_stock + ').', 'error');
                        return;
                    }
                    existing.qty++;
                } else {
                    this.cart.push({
                        id: product.id,
                        name: product.name,
                        price: Number(product.selling_price),
                        stock: Number(product.current_stock),
                        qty: 1
                    });
                }
                this.search = '';
                this.calculateTotal();
                this.notifyPos('+ ' + product.name + ' masuk keranjang.', 'success');
            },
            updateQty(index, newQty) {
                const item = this.cart[index];
                let qty = Math.max(1, parseInt(newQty) || 1);
                if (qty > item.stock) {
                    this.notifyPos('Stok tersedia hanya ' + item.stock, 'error');
                    qty = item.stock;
                }
                item.qty = qty;
                this.calculateTotal();
            },
            addFirstResult() {
                const q = this.search.trim().toLowerCase();
                if (!q) return;

                const exactBarcode = this.products.find(p => p.barcode && String(p.barcode).trim().toLowerCase() === q);
                if (exactBarcode) {
                    this.addToCart(exactBarcode);
                    return;
                }

                if (this.filteredProducts.length > 0) {
                    this.addToCart(this.filteredProducts[0]);
                    return;
                }

                this.notifyPos('Barcode / produk "' + this.search.trim() + '" tidak ditemukan.', 'error');
                this.search = '';
            },
            removeFromCart(index) {
                this.cart.splice(index, 1);
                this.calculateTotal();
            },
            calculateTotal() {
                if (this.selectedEmployee) {
                    const select = document.querySelector('select[x-model="selectedEmployee"]');
                    const selectedOption = select.options[select.selectedIndex];
                    const discountPercent = parseFloat(selectedOption?.getAttribute('data-discount')) || 0;
                    this.discountAmount = this.subTotal * (discountPercent / 100);
                } else {
                    this.discountAmount = 0;
                }
                if (this.cart.length === 0) {
                    this.paidManually = false;
                }
                if (!this.paidManually) {
                    this.paidAmount = this.grandTotal;
                }
            },
            formatRupiah(value) {
                return new Intl.NumberFormat('id-ID').format(value || 0);
            },
            async processCheckout() {
                if (this.cart.length === 0) return;

                if (this.paymentMethod === 'employee_debt' && !this.selectedEmployee) {
                    this.notifyPos('Silakan pilih karyawan terlebih dahulu untuk Potong Gaji.', 'error');
                    return;
                }
                if (this.paymentMethod === 'deposit') {
                    if (!this.selectedCustomer) {
                        this.notifyPos('Silakan pilih pelanggan terlebih dahulu untuk membayar pakai Saldo Deposit.', 'error');
                        return;
                    }
                    if (this.selectedCustomerDeposit < this.grandTotal) {
                        this.notifyPos('Saldo deposit pelanggan tidak mencukupi (tersisa Rp ' + this.formatRupiah(this.selectedCustomerDeposit) + ').', 'error');
                        return;
                    }
                }
                if (!['employee_debt', 'deposit'].includes(this.paymentMethod) && this.paidAmount < this.grandTotal) {
                    this.notifyPos('Uang pembayaran kurang dari total tagihan!', 'error');
                    return;
                }

                this.isProcessing = true;

                try {
                    const response = await fetch('{{ route("pos.store") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({
                            idempotency_key: this.idempotencyKey,
                            items: this.cart.map(item => ({ product_id: item.id, qty: item.qty })),
                            payment_method: this.paymentMethod,
                            wallet_id: this.walletId || null,
                            paid_amount: ['employee_debt', 'deposit'].includes(this.paymentMethod) ? this.grandTotal : this.paidAmount,
                            employee_id: this.selectedEmployee || null,
                            customer_id: this.selectedCustomer || null
                        })
                    });

                    const data = await response.json();

                    if (response.ok && data.status === 'success') {
                        window.location.href = data.redirect;
                    } else {
                        const firstError = data.errors ? Object.values(data.errors).flat()[0] : null;
                        this.notifyPos(firstError || data.message || 'Gagal memproses transaksi.', 'error');
                        this.isProcessing = false;
                    }
                } catch (error) {
                    this.notifyPos('Terjadi kesalahan koneksi saat memproses transaksi.', 'error');
                    this.isProcessing = false;
                }
            }
        }
    }
</script>
@endpush
