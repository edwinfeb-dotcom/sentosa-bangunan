@extends('layouts.app')

@section('title', 'Cetak Label Barcode')

@section('content')
<div class="row">
    <!-- Panel Pengaturan -->
    <div class="col-md-4 d-print-none">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-3" style="color: #1E293B;">Pengaturan Cetak</h5>
                
                <div class="mb-3">
                    <label class="form-label fw-medium">Pilih Produk</label>
                    <select class="form-select" id="productSelect" x-data x-on:change="window.updatePreview()">
                        <option value="">-- Pilih Produk --</option>
                        @foreach($products as $product)
                            <option value="{{ $product->barcode }}" data-name="{{ $product->name }}" data-price="{{ $product->selling_price }}">
                                {{ $product->name }} ({{ $product->barcode ?? 'Tanpa Barcode' }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-medium">Jumlah Label yang Dicetak</label>
                    <input type="number" id="qtyInput" class="form-control" value="1" min="1" max="50" x-on:input="window.updatePreview()">
                </div>

                <div class="mb-3 form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="showPrice" checked x-on:change="window.updatePreview()">
                    <label class="form-check-label" for="showPrice">Tampilkan Harga</label>
                </div>

                <button onclick="window.print()" class="btn btn-primary w-100 mt-3 d-flex justify-content-center align-items-center gap-2">
                    <i class="ph ph-printer"></i> Cetak Sekarang
                </button>
            </div>
        </div>
    </div>

    <!-- Area Preview Cetak -->
    <div class="col-md-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom pt-3 pb-3 d-print-none">
                <h6 class="fw-bold mb-0">Preview Label</h6>
            </div>
            <div class="card-body p-4 bg-light">
                <div id="print-area" class="d-flex flex-wrap gap-2 justify-content-center">
                    <!-- Barcode akan di-generate di sini oleh JS -->
                    <div class="text-center text-muted py-5">
                        Silakan pilih produk di sebelah kiri untuk melihat preview.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    /* CSS Khusus Label Barcode */
    .barcode-label {
        background: white;
        border: 1px dashed #ccc;
        padding: 10px;
        width: 180px; /* Lebar label */
        text-align: center;
        box-sizing: border-box;
    }
    .barcode-label .product-name {
        font-size: 0.75rem;
        font-weight: 700;
        margin-bottom: 5px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .barcode-label svg {
        max-width: 100%;
        height: auto;
    }
    .barcode-label .product-price {
        font-size: 0.85rem;
        font-weight: 800;
        margin-top: 5px;
    }

    /* Aturan saat tombol Print ditekan */
    @media print {
        body { background-color: white !important; }
        #sidebar-wrapper, .navbar, .d-print-none { display: none !important; }
        #page-content-wrapper { width: 100% !important; margin: 0 !important; padding: 0 !important; }
        .container-fluid { padding: 0 !important; }
        .col-md-8 { width: 100% !important; max-width: 100% !important; flex: 0 0 100% !important; }
        .card { border: none !important; box-shadow: none !important; }
        .card-body { padding: 0 !important; background: white !important; }
        #print-area { justify-content: flex-start !important; gap: 0 !important; }
        .barcode-label { border: 1px solid #eee; page-break-inside: avoid; }
    }
</style>

@push('scripts')
<script>
    window.updatePreview = function() {
        const select = document.getElementById('productSelect');
        const qty = parseInt(document.getElementById('qtyInput').value) || 1;
        const showPrice = document.getElementById('showPrice').checked;
        const printArea = document.getElementById('print-area');

        // Kosongkan area print
        printArea.innerHTML = '';

        // Jika belum pilih produk
        if (!select.value) {
            printArea.innerHTML = '<div class="text-center text-muted py-5">Silakan pilih produk di sebelah kiri untuk melihat preview.</div>';
            return;
        }

        const selectedOption = select.options[select.selectedIndex];
        const barcodeValue = select.value;
        const productName = selectedOption.getAttribute('data-name');
        const productPrice = selectedOption.getAttribute('data-price');
        const formattedPrice = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(productPrice);

        // Looping sebanyak Qty
        for (let i = 0; i < qty; i++) {
            // Buat elemen label
            let labelDiv = document.createElement('div');
            labelDiv.className = 'barcode-label m-1';
            
            // HTML isi label
            let html = `<div class="product-name">${productName}</div>`;
            html += `<svg class="barcode-svg"></svg>`;
            if (showPrice) {
                html += `<div class="product-price">${formattedPrice}</div>`;
            }
            
            labelDiv.innerHTML = html;
            printArea.appendChild(labelDiv);

            // Generate Barcode menggunakan JsBarcode
            let svgElement = labelDiv.querySelector('.barcode-svg');
            JsBarcode(svgElement, barcodeValue, {
                format: "CODE128",
                lineColor: "#000",
                width: 1.5,
                height: 40,
                displayValue: true,
                fontSize: 12,
                margin: 0
            });
        }
    }
</script>
@endpush
@endsection