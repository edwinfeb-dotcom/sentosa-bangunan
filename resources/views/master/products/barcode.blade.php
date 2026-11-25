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
                    <select class="form-select" id="productSelect">
                        <option value="">-- Pilih Produk --</option>
                        @foreach($products as $product)
                            <option value="{{ $product->barcode }}" data-id="{{ $product->id }}" data-name="{{ $product->name }}" data-price="{{ $product->selling_price }}">
                                {{ $product->name }} ({{ $product->barcode ?: 'Tanpa Barcode' }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-medium">Jumlah Label yang Dicetak</label>
                    <input type="number" id="qtyInput" class="form-control" value="1" min="1" max="100">
                </div>

                <div class="mb-3 form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="showPrice" checked>
                    <label class="form-check-label" for="showPrice">Tampilkan Harga</label>
                </div>

                <button type="button" id="printBarcodeBtn" onclick="window.print()" class="btn btn-primary w-100 mt-3 d-flex justify-content-center align-items-center gap-2" disabled>
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
                    <div class="text-center text-muted py-5">
                        Silakan pilih produk di sebelah kiri untuk melihat preview.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .barcode-label {
        background: white;
        border: 1px dashed #ccc;
        padding: 10px;
        width: 180px;
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

    @media print {
        body { background-color: white !important; }
        .app-sidebar, .app-topbar, .navbar, .d-print-none { display: none !important; }
        .app-main, .page-content { width: 100% !important; margin: 0 !important; padding: 0 !important; }
        .col-md-8 { width: 100% !important; max-width: 100% !important; flex: 0 0 100% !important; }
        .card { border: none !important; box-shadow: none !important; }
        .card-body { padding: 0 !important; background: white !important; }
        #print-area { justify-content: flex-start !important; gap: 0 !important; }
        .barcode-label { border: 1px solid #eee; page-break-inside: avoid; }
    }
</style>

@push('scripts')
<script>
    window.updatePreview = async function() {
        const select = document.getElementById('productSelect');
        const qtyInput = document.getElementById('qtyInput');
        const qty = Math.min(100, Math.max(1, parseInt(qtyInput.value, 10) || 1));
        const showPrice = document.getElementById('showPrice').checked;
        const printArea = document.getElementById('print-area');
        const printBtn = document.getElementById('printBarcodeBtn');

        printArea.innerHTML = '';

        const selectedOption = select.options[select.selectedIndex];
        if (!selectedOption || select.selectedIndex === 0) {
            if (printBtn) printBtn.disabled = true;
            printArea.innerHTML = '<div class="text-center text-muted py-5">Silakan pilih produk di sebelah kiri untuk melihat preview.</div>';
            return;
        }

        const barcodeValue = (select.value || '').trim();
        const productName = selectedOption.getAttribute('data-name') || '';
        const productPrice = parseFloat(selectedOption.getAttribute('data-price')) || 0;
        const productId = selectedOption.getAttribute('data-id');

        if (!barcodeValue) {
            if (printBtn) printBtn.disabled = true;
            const editBase = @json(url('master/products'));
            const alertBox = document.createElement('div');
            alertBox.className = 'alert alert-warning border-0 text-center my-3 w-100';
            alertBox.innerHTML = `Produk <strong></strong> belum memiliki kode barcode. <a href="${editBase}/${productId}/edit" class="alert-link">Isi barcode di Edit Produk</a> terlebih dahulu.`;
            alertBox.querySelector('strong').textContent = productName;
            printArea.appendChild(alertBox);
            return;
        }

        const JsBarcode = await window.loadJsBarcode();
        const formattedPrice = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(productPrice);

        for (let i = 0; i < qty; i++) {
            const labelDiv = document.createElement('div');
            labelDiv.className = 'barcode-label m-1';

            const nameEl = document.createElement('div');
            nameEl.className = 'product-name';
            nameEl.textContent = productName;
            labelDiv.appendChild(nameEl);

            const svgElement = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
            svgElement.setAttribute('class', 'barcode-svg');
            labelDiv.appendChild(svgElement);

            if (showPrice) {
                const priceEl = document.createElement('div');
                priceEl.className = 'product-price';
                priceEl.textContent = formattedPrice;
                labelDiv.appendChild(priceEl);
            }

            printArea.appendChild(labelDiv);

            try {
                JsBarcode(svgElement, barcodeValue, {
                    format: 'CODE128',
                    lineColor: '#000',
                    width: 1.5,
                    height: 40,
                    displayValue: true,
                    fontSize: 12,
                    margin: 0
                });
            } catch (err) {
                printArea.innerHTML = '<div class="alert alert-danger border-0 w-100">Format barcode tidak valid untuk CODE128.</div>';
                if (printBtn) printBtn.disabled = true;
                return;
            }
        }

        if (printBtn) printBtn.disabled = false;
    };

    document.addEventListener('DOMContentLoaded', function () {
        document.getElementById('productSelect')?.addEventListener('change', window.updatePreview);
        document.getElementById('qtyInput')?.addEventListener('input', window.updatePreview);
        document.getElementById('showPrice')?.addEventListener('change', window.updatePreview);
    });
</script>
@endpush
@endsection
