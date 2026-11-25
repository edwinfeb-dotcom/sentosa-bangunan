@extends('layouts.public')

@section('title', 'Katalog Produk')

@section('body-attrs')
x-data="publicCart()" @keydown.window.escape="isCartOpen = false"
@endsection

@push('styles')
<style>
    .catalog-hero {
        background-color: var(--sb-surface);
        border-bottom: 1px solid var(--sb-border);
        padding: 2.5rem 0;
    }
    .catalog-hero-aside {
        background-color: var(--sb-surface-muted);
        border: 1px solid var(--sb-border);
        border-radius: 14px;
    }
    .category-strip {
        display: flex;
        gap: 0.5rem;
        overflow-x: auto;
        padding-bottom: 0.25rem;
        scrollbar-width: thin;
    }
    .category-pill {
        white-space: nowrap;
        padding: 0.42rem 0.9rem;
        border-radius: 999px;
        font-size: 0.8rem;
        font-weight: 600;
        text-decoration: none;
        border: 1px solid var(--sb-border);
        background: var(--sb-surface);
        color: var(--sb-ink-secondary);
        transition: all 0.14s ease;
    }
    .category-pill:hover {
        border-color: var(--sb-ink);
        color: var(--sb-ink);
    }
    .category-pill.active {
        background-color: var(--sb-ink);
        border-color: var(--sb-ink);
        color: #ffffff;
    }
    .catalog-card {
        border-radius: 12px;
        border: 1px solid var(--sb-border);
        background: var(--sb-surface);
        transition: border-color 0.18s ease, transform 0.18s ease, box-shadow 0.18s ease;
        overflow: hidden;
        height: 100%;
    }
    .catalog-card:hover {
        border-color: var(--sb-border-strong);
        transform: translateY(-2px);
        box-shadow: 0 12px 24px -10px rgba(24, 24, 27, 0.08);
    }
    .catalog-img-wrap {
        height: 175px;
        background: var(--sb-canvas);
        border-bottom: 1px solid var(--sb-border);
        overflow: hidden;
        position: relative;
    }
    .catalog-img-wrap img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.35s ease;
    }
    .catalog-card:hover .catalog-img-wrap img {
        transform: scale(1.04);
    }
    .floating-cart-btn {
        position: fixed;
        bottom: 24px;
        right: 24px;
        background: var(--sb-ink);
        color: #fff;
        border: 1px solid rgba(255, 255, 255, 0.14);
        border-radius: 999px;
        padding: 0.78rem 1.3rem;
        font-weight: 700;
        font-size: 0.88rem;
        box-shadow: 0 14px 30px rgba(24, 24, 27, 0.25);
        z-index: 1030;
        display: flex;
        align-items: center;
        gap: 0.65rem;
        cursor: pointer;
        transition: background-color 0.15s ease, transform 0.15s ease;
    }
    .floating-cart-btn:hover {
        background: var(--sb-accent);
        transform: translateY(-2px);
    }
    .cart-drawer {
        position: fixed;
        top: 0;
        right: 0;
        bottom: 0;
        width: 100%;
        max-width: 420px;
        background: var(--sb-surface);
        border-left: 1px solid var(--sb-border);
        z-index: 1055;
        display: flex;
        flex-direction: column;
        box-shadow: -12px 0 36px rgba(24, 24, 27, 0.14);
    }
    .cart-backdrop {
        position: fixed;
        inset: 0;
        background: rgba(24, 24, 27, 0.42);
        backdrop-filter: blur(2px);
        z-index: 1050;
    }
</style>
@endpush

@section('content')
<!-- Architectural Showroom Hero -->
<section class="catalog-hero mb-4">
    <div class="container">
        <div class="row g-4 align-items-center">
            <div class="col-lg-7">
                <span class="badge border px-3 py-2 mb-3" style="background-color: var(--sb-accent-soft); border-color: var(--sb-accent-border) !important; color: var(--sb-accent);">
                    <i class="ph ph-buildings me-1"></i> KATALOG MATERIAL REAL-TIME
                </span>
                <h1 class="fw-bold text-dark mb-2" style="font-size: clamp(1.7rem, 3.2vw, 2.55rem); letter-spacing: -0.03em; line-height: 1.15;">
                    Bangun proyek Anda dengan stok &amp; harga transparan.
                </h1>
                <p class="text-secondary mb-3" style="max-width: 580px; line-height: 1.6;">
                    Cek ketersediaan bahan bangunan secara langsung, pesan online tanpa harus antre, dan nikmati gratis ongkir armada toko untuk belanja minimal <strong>Rp 1.000.000</strong>.
                </p>
                <div class="d-flex flex-wrap gap-2">
                    <span class="badge bg-light text-dark border px-3 py-2"><i class="ph ph-check-circle text-success me-1"></i> Stok Real-Time</span>
                    <span class="badge bg-light text-dark border px-3 py-2"><i class="ph ph-truck text-accent me-1"></i> Antar Armada Toko</span>
                    <span class="badge bg-light text-dark border px-3 py-2"><i class="ph ph-storefront text-dark me-1"></i> Bisa Ambil di Toko</span>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="catalog-hero-aside p-4">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <div class="text-uppercase fw-bold text-muted" style="font-size: 0.68rem; letter-spacing: 0.08em;">Layanan Pelanggan</div>
                            <h6 class="fw-bold text-dark mb-0 mt-1">Sudah pernah memesan?</h6>
                        </div>
                        <span class="badge bg-white text-dark border">{{ $products->total() }} Produk</span>
                    </div>
                    <p class="small text-secondary mb-3">
                        Masukkan kode pesanan (<code>PUB-...</code>) untuk melihat status pengemasan dan pengiriman armada toko secara langsung.
                    </p>
                    <form action="{{ route('catalog.track') }}" method="GET" class="d-flex gap-2">
                        <input type="text" name="order_code" class="form-control form-control-sm" placeholder="Contoh: PUB-20261124-XXXX" required>
                        <button type="submit" class="btn btn-dark btn-sm flex-shrink-0">
                            <i class="ph ph-magnifying-glass"></i> Lacak
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="container">
    <!-- Filter & Search Toolbar -->
    <div class="card mb-4">
        <div class="card-body p-3 p-md-4">
            <form action="{{ route('catalog.index') }}" method="GET" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small text-muted mb-1">Cari Material / Barcode</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="ph ph-magnifying-glass text-muted"></i></span>
                        <input type="text" class="form-control border-start-0" name="search" value="{{ request('search') }}" placeholder="Contoh: Semen, Cat, Pipa...">
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small text-muted mb-1">Kategori</label>
                    <select class="form-select" name="category">
                        <option value="">Semua</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ request('category') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small text-muted mb-1">Urutkan</label>
                    <select class="form-select" name="sort">
                        <option value="latest" {{ $sort === 'latest' ? 'selected' : '' }}>Terbaru</option>
                        <option value="price_asc" {{ $sort === 'price_asc' ? 'selected' : '' }}>Harga Termurah</option>
                        <option value="price_desc" {{ $sort === 'price_desc' ? 'selected' : '' }}>Harga Termahal</option>
                        <option value="name_asc" {{ $sort === 'name_asc' ? 'selected' : '' }}>Nama A-Z</option>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small text-muted mb-1">Harga Maks (Rp)</label>
                    <input type="number" class="form-control" name="max_price" value="{{ request('max_price') }}" placeholder="Tanpa batas" min="0">
                </div>
                <div class="col-6 col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100">Terapkan</button>
                    @if(request()->hasAny(['search', 'category', 'min_price', 'max_price', 'sort']))
                        <a href="{{ route('catalog.index') }}" class="btn btn-light border" title="Reset Filter">
                            <i class="ph ph-arrow-counter-clockwise"></i>
                        </a>
                    @endif
                </div>
            </form>

            <!-- Category Pill Strip -->
            @if($categories->isNotEmpty())
                <div class="category-strip mt-3 pt-3 border-top">
                    <a href="{{ route('catalog.index', request()->except(['category', 'page'])) }}"
                       class="category-pill {{ !request('category') ? 'active' : '' }}">
                        Semua Kategori
                    </a>
                    @foreach($categories as $cat)
                        <a href="{{ route('catalog.index', array_merge(request()->except('page'), ['category' => $cat->id])) }}"
                           class="category-pill {{ request('category') == $cat->id ? 'active' : '' }}">
                            {{ $cat->name }}
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <!-- Product Grid -->
    <div class="row g-3 g-md-4">
        @forelse($products as $product)
            <div class="col-6 col-md-4 col-lg-3">
                <div class="catalog-card d-flex flex-column">
                    <div class="catalog-img-wrap">
                        @if($product->image)
                            <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}" loading="lazy">
                        @else
                            <div class="d-flex align-items-center justify-content-center h-100">
                                <i class="ph ph-package fs-1 text-muted opacity-25"></i>
                            </div>
                        @endif
                        <span class="position-absolute top-0 end-0 m-2 badge {{ $product->current_stock > 0 ? 'bg-white text-dark border' : 'bg-danger text-white' }}">
                            {{ $product->current_stock > 0 ? 'Stok: ' . $product->current_stock . ' ' . $product->unit : 'Habis' }}
                        </span>
                    </div>

                    <div class="p-3 d-flex flex-column flex-grow-1">
                        <div class="text-accent fw-bold text-uppercase mb-1" style="font-size: 0.66rem; letter-spacing: 0.08em;">
                            {{ $product->category->name ?? 'Umum' }}
                        </div>
                        <h6 class="fw-bold text-dark mb-1" style="font-size: 0.94rem; line-height: 1.35; letter-spacing: -0.01em;">
                            {{ $product->name }}
                        </h6>
                        <p class="text-muted small mb-3" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                            {{ $product->description ?? 'Material bangunan berkualitas tinggi.' }}
                        </p>

                        <div class="mt-auto pt-2 border-top">
                            <div class="fw-bold text-dark tabular-nums mb-2" style="font-size: 1.05rem;">
                                Rp {{ number_format($product->selling_price, 0, ',', '.') }}
                                <span class="text-muted fw-normal small">/ {{ $product->unit }}</span>
                            </div>
                            <button type="button"
                                    class="btn {{ $product->current_stock > 0 ? 'btn-dark' : 'btn-light text-muted border' }} w-100 btn-sm"
                                    :disabled="{{ $product->current_stock <= 0 ? 'true' : 'false' }}"
                                    @click="addToCart({ id: {{ $product->id }}, name: @js($product->name), price: {{ (float) $product->selling_price }}, unit: @js($product->unit), stock: {{ $product->current_stock }} })">
                                <i class="ph ph-shopping-cart-simple"></i>
                                {{ $product->current_stock > 0 ? 'Tambah ke Keranjang' : 'Stok Kosong' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="ph ph-package fs-1 text-muted opacity-50 d-block mb-2"></i>
                        <h5 class="fw-bold text-dark">Produk tidak ditemukan</h5>
                        <p class="text-muted small mb-3">Coba ubah kata kunci pencarian atau reset filter kategori.</p>
                        <a href="{{ route('catalog.index') }}" class="btn btn-primary btn-sm">Lihat Semua Produk</a>
                    </div>
                </div>
            </div>
        @endforelse
    </div>

    <div class="d-flex justify-content-center mt-4">
        {{ $products->links() }}
    </div>
</div>

<!-- Floating Cart Button -->
<button type="button" class="floating-cart-btn" x-cloak x-show="cart.length > 0" @click="isCartOpen = true" x-transition>
    <i class="ph ph-shopping-cart fs-5"></i>
    <span x-text="totalItems + ' Item'"></span>
    <span class="badge bg-white text-dark tabular-nums">Rp <span x-text="formatRupiah(grandTotal)"></span></span>
</button>

<!-- Cart Backdrop -->
<div class="cart-backdrop" x-cloak x-show="isCartOpen" @click="isCartOpen = false" x-transition.opacity></div>

<!-- Cart Drawer -->
<aside class="cart-drawer" x-cloak x-show="isCartOpen" x-transition>
    <div class="p-3 border-bottom d-flex justify-content-between align-items-center" style="background-color: var(--sb-surface-muted);">
        <h5 class="fw-bold mb-0 text-dark" style="font-size: 1rem;"><i class="ph ph-shopping-cart me-2 text-accent"></i>Keranjang Belanja</h5>
        <button type="button" class="btn-close" @click="isCartOpen = false"></button>
    </div>

    <div x-show="cartNotice" x-transition class="px-3 py-2 small fw-semibold bg-warning bg-opacity-25 text-dark border-bottom" x-text="cartNotice"></div>

    <!-- Free Shipping Progress -->
    <div class="px-3 py-2 border-bottom bg-light" x-show="cart.length > 0">
        <div class="d-flex justify-content-between small mb-1">
            <span class="text-secondary fw-medium" x-text="grandTotal >= 1000000 ? 'Gratis ongkir armada terpenuhi!' : 'Tambah Rp ' + formatRupiah(1000000 - grandTotal) + ' untuk gratis ongkir'"></span>
            <span class="fw-bold tabular-nums" x-text="Math.min(100, Math.round((grandTotal / 1000000) * 100)) + '%'"></span>
        </div>
        <div class="progress" style="height: 5px;">
            <div class="progress-bar" :class="grandTotal >= 1000000 ? 'bg-success' : 'bg-primary'" :style="'width:' + Math.min(100, (grandTotal / 1000000) * 100) + '%'"></div>
        </div>
    </div>

    <div class="p-3 flex-grow-1" style="overflow-y: auto;">
        <template x-for="(item, index) in cart" :key="item.id">
            <div class="d-flex align-items-center gap-2 pb-3 mb-3 border-bottom">
                <div class="flex-grow-1">
                    <div class="fw-bold text-dark small" x-text="item.name"></div>
                    <div class="text-accent fw-semibold small tabular-nums" x-text="'Rp ' + formatRupiah(item.price) + ' / ' + (item.unit || 'Pcs')"></div>
                </div>
                <div class="d-flex align-items-center gap-1">
                    <button type="button" class="btn btn-sm btn-light border px-2" @click="updateQty(index, item.qty - 1)">-</button>
                    <input type="number" class="form-control form-control-sm text-center px-1 tabular-nums" style="width: 48px;" x-model.number="item.qty" min="1" @change="updateQty(index, item.qty)">
                    <button type="button" class="btn btn-sm btn-light border px-2" @click="updateQty(index, item.qty + 1)">+</button>
                </div>
                <div class="fw-bold text-end small tabular-nums" style="min-width: 85px;" x-text="'Rp ' + formatRupiah(item.price * item.qty)"></div>
                <button type="button" class="btn btn-sm text-danger p-1" @click="removeFromCart(index)"><i class="ph ph-trash"></i></button>
            </div>
        </template>

        <div x-show="cart.length === 0" class="text-center py-5 text-muted">
            <i class="ph ph-shopping-cart fs-1 opacity-25 d-block mb-2"></i>
            <div class="fw-bold text-dark">Keranjang masih kosong</div>
            <div class="small">Pilih material bangunan terlebih dahulu.</div>
        </div>
    </div>

    <div class="p-3 border-top" style="background-color: var(--sb-surface-muted);" x-show="cart.length > 0">
        <div class="d-flex justify-content-between mb-3">
            <span class="text-secondary fw-semibold">Subtotal</span>
            <span class="fw-bold fs-5 text-dark tabular-nums" x-text="'Rp ' + formatRupiah(grandTotal)"></span>
        </div>
        <button type="button" class="btn btn-primary btn-lg w-100" @click="goToCheckout()">
            Lanjut ke Checkout <i class="ph ph-arrow-right"></i>
        </button>
    </div>
</aside>
@endsection

@push('scripts')
<script>
    function publicCart() {
        const isLoggedIn = {{ auth()->check() ? 'true' : 'false' }};
        const serverCart = @json($dbCart);
        let localCart = [];
        try {
            localCart = JSON.parse(localStorage.getItem('public_cart')) || [];
        } catch (e) {
            localCart = [];
        }

        let initialCart = localCart;
        if (isLoggedIn) {
            if (serverCart.length > 0 && localCart.length === 0) {
                initialCart = serverCart;
            } else if (serverCart.length > 0 && localCart.length > 0) {
                const map = new Map();
                serverCart.forEach(i => map.set(i.id, { ...i }));
                localCart.forEach(i => {
                    if (!map.has(i.id)) map.set(i.id, { ...i });
                });
                initialCart = Array.from(map.values());
            }
        }

        return {
            cart: initialCart,
            isCartOpen: false,
            cartNotice: '',
            cartNoticeTimer: null,

            init() {
                this.saveCart();
            },
            notifyCart(msg) {
                this.cartNotice = msg;
                if (this.cartNoticeTimer) clearTimeout(this.cartNoticeTimer);
                this.cartNoticeTimer = setTimeout(() => { this.cartNotice = ''; }, 3000);
            },
            get totalItems() {
                return this.cart.reduce((sum, item) => sum + Number(item.qty || 0), 0);
            },
            get grandTotal() {
                return this.cart.reduce((sum, item) => sum + (Number(item.price) * Number(item.qty || 0)), 0);
            },
            addToCart(product) {
                if (product.stock !== undefined && product.stock <= 0) {
                    this.notifyCart('Stok ' + product.name + ' sedang kosong.');
                    this.isCartOpen = true;
                    return;
                }
                const existing = this.cart.find(i => i.id === product.id);
                if (existing) {
                    existing.stock = product.stock;
                    existing.price = product.price;
                    if (product.stock !== undefined && existing.qty >= product.stock) {
                        this.notifyCart('Jumlah ' + product.name + ' sudah mencapai batas stok (' + product.stock + ').');
                        this.isCartOpen = true;
                        return;
                    }
                    existing.qty++;
                } else {
                    this.cart.push({ ...product, qty: 1 });
                }
                this.saveCart();
                this.isCartOpen = true;
            },
            updateQty(index, newQty) {
                const item = this.cart[index];
                if (!item) return;
                let qty = Math.max(1, parseInt(newQty) || 1);
                if (item.stock !== undefined && item.stock > 0 && qty > item.stock) {
                    qty = item.stock;
                    this.notifyCart('Stok ' + item.name + ' tersedia hanya ' + item.stock + ' ' + (item.unit || '') + '.');
                }
                item.qty = qty;
                this.saveCart();
            },
            removeFromCart(index) {
                this.cart.splice(index, 1);
                this.saveCart();
            },
            saveCart() {
                localStorage.setItem('public_cart', JSON.stringify(this.cart));
                if (isLoggedIn) {
                    fetch('{{ route("catalog.cart-sync") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({ cart: this.cart })
                    }).catch(() => {});
                }
            },
            formatRupiah(value) {
                return new Intl.NumberFormat('id-ID').format(value || 0);
            },
            goToCheckout() {
                if (this.cart.length === 0) return;
                window.location.href = "{{ route('catalog.checkout') }}";
            }
        }
    }
</script>
@endpush
