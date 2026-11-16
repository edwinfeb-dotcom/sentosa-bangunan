<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Katalog Produk - Sentosa Bangunan</title>
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    <style>
        body { background-color: #F1F5F9; font-family: 'Plus Jakarta Sans', sans-serif; }
        
        /* Navbar Modern */
        .navbar-custom {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid #E2E8F0;
        }

        /* Hero Section Mewah */
        .hero-section {
            background: linear-gradient(135deg, #0F172A 0%, #1E3A8A 100%);
            color: white;
            padding: 80px 0;
            border-radius: 0 0 30px 30px;
            position: relative;
            overflow: hidden;
        }
        .hero-section::before {
            content: '';
            position: absolute;
            top: -50%; right: -20%;
            width: 500px; height: 500px;
            background: radial-gradient(circle, rgba(59,130,246,0.3) 0%, transparent 70%);
            border-radius: 50%;
        }

        /* Kartu Produk */
        .product-card {
            border-radius: 16px;
            border: none;
            background: #fff;
            transition: all 0.3s ease;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
            height: 100%;
        }
        .product-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);
        }
        .product-img-wrapper {
            height: 180px;
            overflow: hidden;
            position: relative;
            background-color: #F8FAFC;
        }
        .product-img {
            width: 100%; height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }
        .product-card:hover .product-img {
            transform: scale(1.08); /* Efek Zoom */
        }
        .product-category {
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #3B82F6;
        }
        .product-title {
            font-size: 0.95rem;
            font-weight: 800;
            color: #1E293B;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .product-price {
            font-size: 1.1rem;
            font-weight: 800;
            color: #1E3A8A;
        }

        /* Floating Cart Button */
        .floating-cart {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: #1E3A8A;
            color: white;
            border-radius: 50px;
            padding: 15px 25px;
            font-weight: bold;
            font-size: 1.1rem;
            box-shadow: 0 10px 25px rgba(30, 58, 138, 0.4);
            cursor: pointer;
            z-index: 1040;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .floating-cart:hover {
            background: #152C6B;
            transform: translateY(-5px);
        }
    </style>
</head>
<body x-data="publicCart()">

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-custom py-3 sticky-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="#">
                <img src="{{ asset('images/logo.png') }}" alt="Logo Sentosa" style="height: 40px; max-width: 100%; object-fit: contain;">
            </a>
            <div class="ms-auto d-flex align-items-center gap-3">
                <a href="{{ route('login') }}" class="btn btn-outline-primary btn-sm fw-bold px-4 rounded-pill">
                    <i class="ph ph-sign-in me-1"></i> Login
                </a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <div class="hero-section text-center mb-5">
        <div class="container position-relative z-1">
            <span class="badge bg-primary bg-opacity-25 text-white mb-3 px-3 py-2 rounded-pill border border-primary border-opacity-50">Toko Bangunan Terpercaya</span>
            <h1 class="fw-bold mb-3" style="font-size: 2.8rem;">Bangun Rumah Impian Anda<br>Bersama Kami.</h1>
            <p class="fs-5 text-white-50 mb-0 mx-auto" style="max-width: 600px;">Temukan material bangunan berkualitas tinggi dengan harga terbaik. Pesan sekarang, kami antar ke lokasi Anda.</p>
        </div>
    </div>

    <!-- Konten Utama -->
    <div class="container mb-5">
        
        <!-- Filter & Pencarian -->
        <div class="card border-0 shadow-sm mb-5 mx-auto" style="border-radius: 16px; max-width: 800px; margin-top: -40px; position: relative; z-index: 2;">
            <div class="card-body p-3">
                <form action="{{ route('catalog.index') }}" method="GET" class="row g-2 align-items-center">
                    <div class="col-md-5">
                        <div class="input-group bg-light rounded-3">
                            <span class="input-group-text bg-transparent border-0"><i class="ph ph-magnifying-glass fs-5 text-muted"></i></span>
                            <input type="text" class="form-control bg-transparent border-0 ps-0" name="search" placeholder="Cari nama material..." value="{{ request('search') }}" style="box-shadow: none;">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <select class="form-select bg-light border-0" name="category" style="box-shadow: none;">
                            <option value="">Semua Kategori</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" {{ request('category') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary w-100 fw-bold rounded-3 py-2">Cari Produk</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Grid Produk -->
        <div class="row g-4">
            @forelse($products as $product)
            <div class="col-6 col-md-4 col-lg-3">
                <div class="card product-card">
                    <div class="product-img-wrapper">
                        @if($product->image)
                            <img src="{{ asset('storage/' . $product->image) }}" class="product-img" alt="{{ $product->name }}" loading="lazy">
                        @else
                            <div class="d-flex align-items-center justify-content-center h-100">
                                <i class="ph ph-image fs-1 text-muted opacity-25"></i>
                            </div>
                        @endif
                    </div>
                    <div class="card-body d-flex flex-column p-3">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <small class="product-category">{{ $product->category->name ?? 'Umum' }}</small>
                            <small class="text-muted fw-medium bg-light px-2 py-1 rounded" style="font-size: 0.7rem;">Stok: {{ $product->current_stock }}</small>
                        </div>
                        <h6 class="product-title mb-2">{{ $product->name }}</h6>
                        <p class="text-muted mb-3" style="font-size: 0.8rem; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                            {{ $product->description ?? 'Material bangunan berkualitas.' }}
                        </p>
                        
                        <div class="mt-auto">
                            <div class="product-price mb-3">
                                Rp {{ number_format($product->selling_price, 0, ',', '.') }} <span class="text-muted fw-normal" style="font-size: 0.8rem;">/ {{ $product->unit }}</span>
                            </div>
                            <button class="btn btn-outline-primary w-100 fw-bold rounded-3" @click="addToCart({{ $product }})">
                                <i class="ph ph-shopping-cart-simple me-1"></i> Tambah
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            @empty
            <div class="col-12 text-center py-5">
                <div class="bg-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3 shadow-sm" style="width: 80px; height: 80px;">
                    <i class="ph ph-package fs-1 text-muted opacity-50"></i>
                </div>
                <h5 class="text-muted fw-bold">Produk tidak ditemukan.</h5>
                <p class="text-muted">Coba gunakan kata kunci lain.</p>
            </div>
            @endforelse
        </div>

        <!-- Pagination -->
        <div class="d-flex justify-content-center mt-5">
            {{ $products->withQueryString()->links() }}
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-white border-top py-4 mt-auto">
        <div class="container d-flex flex-column flex-md-row justify-content-between align-items-center">
            <div class="fw-bold fs-5 mb-2 mb-md-0" style="color: #1E3A8A;">
                <i class="ph ph-buildings me-1"></i> Sentosa Bangunan
            </div>
            <div class="text-muted small">&copy; {{ date('Y') }} Sentosa Bangunan. All rights reserved.</div>
        </div>
    </footer>

    <!-- Floating Cart Button (Hanya muncul jika ada barang) -->
    <div class="floating-cart" x-show="cart.length > 0" @click="isCartOpen = true" x-transition>
        <i class="ph ph-shopping-cart fs-4"></i>
        <span x-text="cart.length + ' Barang'"></span>
        <span class="badge bg-white text-primary ms-2">Rp <span x-text="formatRupiah(grandTotal)"></span></span>
    </div>

    <!-- OFFCANVAS KERANJANG -->
    <div class="offcanvas offcanvas-end" tabindex="-1" id="cartOffcanvas" :class="isCartOpen ? 'show' : ''" style="visibility: visible; width: 400px;" x-show="isCartOpen" x-transition>
        <div class="offcanvas-header border-bottom bg-light">
            <h5 class="offcanvas-title fw-bold"><i class="ph ph-shopping-cart me-2"></i>Keranjang Belanja</h5>
            <button type="button" class="btn-close" @click="isCartOpen = false"></button>
        </div>
        <div class="offcanvas-body p-0">
            <div class="p-3" style="max-height: 65vh; overflow-y: auto;">
                <template x-for="(item, index) in cart" :key="index">
                    <div class="d-flex align-items-center mb-3 pb-3 border-bottom">
                        <div class="flex-grow-1">
                            <div class="fw-bold text-dark" style="font-size: 0.9rem;" x-text="item.name"></div>
                            <div class="text-primary fw-bold" style="font-size: 0.85rem;" x-text="'Rp ' + formatRupiah(item.price)"></div>
                        </div>
                        <div class="d-flex align-items-center gap-2 mx-2">
                            <button class="btn btn-sm btn-light border py-0 px-2" @click="if(item.qty > 1) { item.qty--; saveCart(); }">-</button>
                            <input type="number" class="form-control form-control-sm text-center border-0 bg-light px-0" style="width: 40px;" x-model.number="item.qty" min="1" @input="saveCart()">
                            <button class="btn btn-sm btn-light border py-0 px-2" @click="item.qty++; saveCart();">+</button>
                        </div>
                        <div class="fw-bold ms-2" style="width: 80px; text-align: right; font-size: 0.9rem;" x-text="'Rp ' + formatRupiah(item.price * item.qty)"></div>
                        <button class="btn btn-sm text-danger ms-2" @click="removeFromCart(index)"><i class="ph ph-trash"></i></button>
                    </div>
                </template>
                <div x-show="cart.length === 0" class="text-center py-5 text-muted">
                    <i class="ph ph-shopping-cart fs-1 opacity-25 mb-3 d-block"></i>
                    <h6 class="fw-bold">Keranjang Kosong</h6>
                    <p class="small">Silakan pilih produk terlebih dahulu.</p>
                </div>
            </div>
        </div>
        <div class="offcanvas-footer p-3 border-top bg-light" x-show="cart.length > 0">
            <div class="d-flex justify-content-between mb-3">
                <span class="text-muted fw-medium">Total Tagihan:</span>
                <span class="fw-bold fs-4 text-primary" x-text="'Rp ' + formatRupiah(grandTotal)"></span>
            </div>
            <button class="btn btn-primary w-100 btn-lg fw-bold rounded-3" @click="goToCheckout()">
                Checkout <i class="ph ph-arrow-right ms-1"></i>
            </button>
        </div>
    </div>
    <!-- Overlay Gelap -->
    <div class="offcanvas-backdrop fade show" x-show="isCartOpen" @click="isCartOpen = false" style="display: none;"></div>

    <!-- SCRIPT LANGSUNG DI SINI -->
    <script>
        function publicCart() {
            return {
                cart: JSON.parse(localStorage.getItem('public_cart')) || [],
                isCartOpen: false,

                get grandTotal() {
                    return this.cart.reduce((total, item) => total + (item.price * item.qty), 0);
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
                    this.saveCart();
                    this.isCartOpen = true; 
                },
                removeFromCart(index) {
                    this.cart.splice(index, 1);
                    this.saveCart();
                },
                saveCart() {
                    localStorage.setItem('public_cart', JSON.stringify(this.cart));
                },
                formatRupiah(value) {
                    return new Intl.NumberFormat('id-ID').format(value);
                },
                goToCheckout() {
                    if(this.cart.length === 0) return;
                    window.location.href = "{{ route('catalog.checkout') }}";
                }
            }
        }
    </script>
</body>
</html>