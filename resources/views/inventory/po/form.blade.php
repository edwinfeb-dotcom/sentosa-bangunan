@extends('layouts.app')

@section('title', 'Buat Purchase Order')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-10">
        <div class="d-flex align-items-center mb-4">
            <a href="{{ route('inventory.po.index') }}" class="btn btn-light border btn-sm me-3">
                <i class="ph ph-arrow-left"></i>
            </a>
            <h4 class="fw-bold mb-0" style="color: #1E293B;">Buat Purchase Order Baru</h4>
        </div>

        <div class="card">
            <div class="card-body p-4">
                <form action="{{ route('inventory.po.store') }}" method="POST" x-data="poForm()">
                    @csrf
                    
                    <!-- Info PO -->
                    <div class="row mb-4 bg-light rounded p-3 mx-0">
                        <div class="col-md-4">
                            <label class="form-label fw-medium text-muted small">No. PO</label>
                            <input type="text" class="form-control bg-white" name="po_number" value="{{ $poNumber }}" readonly>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-medium text-muted small">Tanggal PO</label>
                            <input type="date" class="form-control bg-white" name="order_date" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-medium text-muted small">Supplier</label>
                            <select class="form-select bg-white" name="supplier_id" required>
                                <option value="">-- Pilih Supplier --</option>
                                @foreach($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- Tabel Item Dinamis -->
                    <div class="table-responsive mb-3">
                        <table class="table table-bordered align-middle">
                            <thead class="bg-light">
                                <tr>
                                    <th width="40%">Produk</th>
                                    <th width="15%">Qty</th>
                                    <th width="20%">Harga Beli (Rp)</th>
                                    <th width="20%">Subtotal</th>
                                    <th width="5%"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="(item, index) in items" :key="index">
                                    <tr>
                                        <td>
                                            <select class="form-select" :name="`items[${index}][product_id]`" x-model="item.product_id" @change="setProductPrice(index)" required>
                                                <option value="">-- Pilih Produk --</option>
                                                @foreach($products as $product)
                                                    <option value="{{ $product->id }}" data-price="{{ $product->purchase_price }}">
                                                        {{ $product->name }} ({{ $product->unit }})
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <input type="number" class="form-control" :name="`items[${index}][qty]`" x-model.number="item.qty" min="1" @input="calculateTotal()" required>
                                        </td>
                                        <td>
                                            <input type="number" class="form-control" :name="`items[${index}][price]`" x-model.number="item.price" min="0" @input="calculateTotal()" required>
                                        </td>
                                        <td class="fw-medium">
                                            <span x-text="'Rp ' + formatRupiah(item.qty * item.price)"></span>
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm btn-light border text-danger" @click="removeItem(index)" x-show="items.length > 1">
                                                <i class="ph ph-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>

                    <!-- Tombol Tambah Baris & Total -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <button type="button" class="btn btn-outline-primary btn-sm d-flex align-items-center gap-1" @click="addItem()">
                            <i class="ph ph-plus"></i> Tambah Baris
                        </button>
                        <div class="fs-5">
                            Grand Total: <strong class="text-primary" x-text="'Rp ' + formatRupiah(grandTotal)"></strong>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-medium">Catatan (Opsional)</label>
                        <textarea class="form-control" name="notes" rows="2" placeholder="Tambahkan catatan pengiriman..."></textarea>
                    </div>

                    <hr class="mb-4">

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('inventory.po.index') }}" class="btn btn-light border">Batal</a>
                        <button type="submit" class="btn btn-primary">Simpan Purchase Order</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function poForm() {
        return {
            items: [{ product_id: '', qty: 1, price: 0 }],
            get grandTotal() {
                return this.items.reduce((total, item) => total + (item.qty * item.price), 0);
            },
            addItem() {
                this.items.push({ product_id: '', qty: 1, price: 0 });
            },
            removeItem(index) {
                this.items.splice(index, 1);
            },
            setProductPrice(index) {
                let select = document.querySelector(`select[name="items[${index}][product_id]"]`);
                let selectedOption = select.options[select.selectedIndex];
                let price = selectedOption.getAttribute('data-price');
                this.items[index].price = price ? parseInt(price) : 0;
            },
            formatRupiah(value) {
                return new Intl.NumberFormat('id-ID').format(value);
            }
        }
    }
</script>
@endpush
@endsection