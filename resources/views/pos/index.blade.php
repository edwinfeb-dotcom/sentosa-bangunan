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
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-light border btn-sm" data-bs-toggle="modal" data-bs-target="#shiftSalesModal">
            <i class="ph ph-receipt"></i> Struk Shift ({{ $recentSales->count() }})
        </button>
        <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#closeSessionModal">
            <i class="ph ph-lock-key"></i> Tutup Kasir
        </button>
    </div>
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
                            <div class="d-flex align-items-center flex-wrap gap-1 mt-1">
                                <span class="text-muted tabular-nums" style="font-size: 0.76rem;" x-text="'Rp ' + formatRupiah(item.price) + '/' + item.unit_label + ' (Maks: ' + item.stock + ')'"></span>
                                <template x-if="item.has_secondary">
                                    <select class="form-select form-select-sm py-0 px-1 bg-light border-secondary-subtle fw-semibold" style="width: auto; font-size: 0.72rem;" x-model="item.unit_type" @change="switchUnit(index)">
                                        <option value="primary" x-text="item.unit"></option>
                                        <option value="secondary" x-text="item.secondary_unit + ' (Eceran)'"></option>
                                    </select>
                                </template>
                            </div>
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
                                <option value="{{ $cust->id }}" data-deposit="{{ (float) $cust->deposit_balance }}" data-debt="{{ (float) ($cust->debt_balance ?? 0) }}">
                                    {{ $cust->name }} ({{ $cust->points }} ★{{ $cust->deposit_balance > 0 ? ' • Dep: Rp ' . number_format($cust->deposit_balance, 0, ',', '.') : '' }}{{ ($cust->debt_balance ?? 0) > 0 ? ' • Bon: Rp ' . number_format($cust->debt_balance, 0, ',', '.') : '' }})
                                </option>
                            @endforeach
                        </select>
                        <div class="small text-success fw-semibold mt-1 tabular-nums" x-show="selectedCustomerDeposit > 0">
                            Saldo Deposit: Rp <span x-text="formatRupiah(selectedCustomerDeposit)"></span>
                        </div>
                        <div class="small text-danger fw-semibold mt-1 tabular-nums" x-show="selectedCustomerDebt > 0">
                            Sisa Bon: Rp <span x-text="formatRupiah(selectedCustomerDebt)"></span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small text-muted mb-1">Diskon Karyawan</label>
                        <select class="form-select form-select-sm" x-model="selectedEmployee" @change="calculateTotal()">
                            <option value="">Tanpa Diskon Karyawan</option>
                            @foreach($employees as $emp)
                                <option value="{{ $emp->id }}" data-discount="{{ $emp->employee_discount }}">
                                    {{ $emp->name }} ({{ $emp->employee_discount }}%)
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="row g-2 mb-2">
                    <div class="col-6">
                        <label class="form-label small text-muted mb-1">Potongan / Diskon Manual (Rp)</label>
                        <input type="number" class="form-control form-control-sm tabular-nums" min="0" x-model.number="manualDiscount" @input="calculateTotal()" placeholder="0">
                    </div>
                    <div class="col-6">
                        <label class="form-label small text-muted mb-1">Catatan Diskon</label>
                        <input type="text" class="form-control form-control-sm" x-model="discountNote" placeholder="Opsional: Diskon borongan">
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
                    <div class="d-flex justify-content-between small text-warning fw-semibold tabular-nums" x-show="manualDiscount > 0">
                        <span>Potongan Manual</span>
                        <span x-text="'- Rp ' + formatRupiah(manualDiscount)"></span>
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
                            <option value="customer_debt">Bon / Piutang Pelanggan</option>
                            <option value="employee_debt">Potong Gaji</option>
                        </select>
                    </div>
                    <div class="col-6" x-show="!['employee_debt', 'deposit', 'customer_debt'].includes(paymentMethod)">
                        <label class="form-label small text-muted mb-1">Masuk ke Dompet</label>
                        <select class="form-select form-select-sm" x-model="walletId">
                            <option value="">Otomatis</option>
                            @foreach($wallets as $wallet)
                                <option value="{{ $wallet->id }}">{{ $wallet->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div x-show="!['employee_debt', 'deposit', 'customer_debt'].includes(paymentMethod)" class="mb-2">
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
                        :disabled="cart.length === 0 || (!['employee_debt', 'deposit', 'customer_debt'].includes(paymentMethod) && paidAmount < grandTotal) || isProcessing">
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
                        @if(($shiftPettyCashNet ?? 0) != 0)
                        <div class="d-flex justify-content-between mb-1 small tabular-nums">
                            <span class="text-muted">Mutasi Kas Laci Manual:</span>
                            <span class="fw-semibold {{ $shiftPettyCashNet >= 0 ? 'text-success' : 'text-danger' }}">{{ $shiftPettyCashNet >= 0 ? '+' : '-' }} Rp {{ number_format(abs($shiftPettyCashNet), 0, ',', '.') }}</span>
                        </div>
                        @endif
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

<!-- Modal Riwayat Struk Shift Ini -->
<div class="modal fade" id="shiftSalesModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Riwayat Struk Shift Ini ({{ $recentSales->count() }})</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>No. Invoice</th>
                                <th>Jam</th>
                                <th>Pelanggan</th>
                                <th class="text-end">Total</th>
                                <th>Status</th>
                                <th class="text-end">Struk</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentSales as $rs)
                            <tr>
                                <td class="fw-semibold font-monospace">{{ $rs->invoice_number }}</td>
                                <td class="small text-muted">{{ $rs->created_at->format('H:i') }}</td>
                                <td class="small">{{ $rs->customer->name ?? 'Umum' }}</td>
                                <td class="text-end fw-bold tabular-nums">Rp {{ number_format($rs->total_amount, 0, ',', '.') }}</td>
                                <td>
                                    @if($rs->status === 'completed')
                                        <span class="badge bg-success bg-opacity-10 text-success">Selesai</span>
                                    @else
                                        <span class="badge bg-danger bg-opacity-10 text-danger">Batal</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('pos.receipt', $rs->id) }}" class="btn btn-sm btn-light border"><i class="ph ph-printer"></i> Buka</a>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="6" class="text-center py-4 text-muted">Belum ada transaksi pada shift ini.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
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
            manualDiscount: 0,
            discountNote: '',
            noticeText: '',
            noticeType: 'success',
            noticeTimer: null,

            get selectedCustomerDeposit() {
                if (!this.selectedCustomer) return 0;
                const found = this.customersList.find(c => String(c.id) === String(this.selectedCustomer));
                return found ? Number(found.deposit_balance || 0) : 0;
            },
            get selectedCustomerDebt() {
                if (!this.selectedCustomer) return 0;
                const found = this.customersList.find(c => String(c.id) === String(this.selectedCustomer));
                return found ? Number(found.debt_balance || 0) : 0;
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
                if (typeof window.sbToast === 'function' && type === 'error') {
                    window.sbToast(msg, 'error', { title: 'Perhatian Kasir', duration: 3500 });
                }
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
                return Math.max(0, this.subTotal - this.discountAmount - (Number(this.manualDiscount) || 0));
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
                    if (existing.qty >= existing.stock) {
                        this.notifyPos('Jumlah melebihi stok tersedia (' + existing.stock + ' ' + existing.unit_label + ').', 'error');
                        return;
                    }
                    existing.qty++;
                } else {
                    const hasSecondary = Boolean(product.secondary_unit && Number(product.secondary_price) > 0);
                    this.cart.push({
                        id: product.id,
                        name: product.name,
                        unit: product.unit || 'pcs',
                        primary_price: Number(product.selling_price),
                        primary_stock: Number(product.current_stock),
                        secondary_unit: product.secondary_unit || '',
                        secondary_ratio: Number(product.secondary_ratio || 1),
                        secondary_price: Number(product.secondary_price || 0),
                        has_secondary: hasSecondary,
                        unit_type: 'primary',
                        unit_label: product.unit || 'pcs',
                        price: Number(product.selling_price),
                        stock: Number(product.current_stock),
                        qty: 1
                    });
                }
                this.search = '';
                this.calculateTotal();
                this.notifyPos('+ ' + product.name + ' masuk keranjang.', 'success');
            },
            switchUnit(index) {
                const item = this.cart[index];
                if (item.unit_type === 'secondary' && item.has_secondary) {
                    const ratio = item.secondary_ratio > 1 ? item.secondary_ratio : 1;
                    item.price = item.secondary_price;
                    item.unit_label = item.secondary_unit;
                    item.stock = ratio > 1 ? Math.floor(item.primary_stock * ratio) : item.primary_stock;
                } else {
                    item.unit_type = 'primary';
                    item.price = item.primary_price;
                    item.unit_label = item.unit;
                    item.stock = item.primary_stock;
                }
                if (item.qty > item.stock) {
                    item.qty = Math.max(1, item.stock);
                }
                this.calculateTotal();
            },
            updateQty(index, newQty) {
                const item = this.cart[index];
                let qty = Math.max(1, parseInt(newQty) || 1);
                if (qty > item.stock) {
                    this.notifyPos('Stok tersedia hanya ' + item.stock + ' ' + item.unit_label, 'error');
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
                if (this.paymentMethod === 'customer_debt' && !this.selectedCustomer) {
                    this.notifyPos('Silakan pilih pelanggan terlebih dahulu untuk metode Bon / Piutang Pelanggan.', 'error');
                    return;
                }
                if (!['employee_debt', 'deposit', 'customer_debt'].includes(this.paymentMethod) && this.paidAmount < this.grandTotal) {
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
                            items: this.cart.map(item => ({
                                product_id: item.id,
                                qty: item.qty,
                                unit_type: item.unit_type || 'primary'
                            })),
                            payment_method: this.paymentMethod,
                            wallet_id: this.walletId || null,
                            paid_amount: ['employee_debt', 'deposit', 'customer_debt'].includes(this.paymentMethod) ? this.grandTotal : this.paidAmount,
                            manual_discount: Number(this.manualDiscount) || 0,
                            discount_note: this.discountNote || null,
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
