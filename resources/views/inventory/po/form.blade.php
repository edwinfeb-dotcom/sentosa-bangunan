@extends('layouts.app')

@section('title', isset($po) ? 'Edit Purchase Order' : 'Buat Purchase Order')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-10">
        <div class="d-flex align-items-center mb-4">
            <a href="{{ route('inventory.po.index') }}" class="btn btn-light border btn-sm me-3">
                <i class="ph ph-arrow-left"></i>
            </a>
            <h4 class="fw-bold mb-0" style="color: #1E293B;">{{ isset($po) ? 'Edit Purchase Order: ' . $po->po_number : 'Buat Purchase Order Baru' }}</h4>
        </div>

        <div class="card">
            <div class="card-body p-4">
                <form action="{{ isset($po) ? route('inventory.po.update', $po->id) : route('inventory.po.store') }}" method="POST" x-data="poForm()">
                    @csrf
                    @if(isset($po))
                        @method('PUT')
                    @endif

                    <!-- Info PO -->
                    <div class="row mb-4 bg-light rounded p-3 mx-0">
                        <div class="col-md-4">
                            <label class="form-label fw-medium text-muted small">No. PO</label>
                            <input type="text" class="form-control bg-white" name="po_number" value="{{ $poNumber }}" readonly>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-medium text-muted small">Tanggal PO</label>
                            <input type="date" class="form-control bg-white" name="order_date" value="{{ old('order_date', isset($po) ? \Carbon\Carbon::parse($po->order_date)->format('Y-m-d') : date('Y-m-d')) }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-medium text-muted small">Supplier</label>
                            <select class="form-select bg-white" name="supplier_id" required>
                                <option value="">-- Pilih Supplier --</option>
                                @foreach($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}" {{ old('supplier_id', $po->supplier_id ?? '') == $supplier->id ? 'selected' : '' }}>{{ $supplier->name }}</option>
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
                                <template x-for="(item, index) in items" :key="item.uid">
                                    <tr>
                                        <td>
                                            <select class="form-select" :name="`items[${index}][product_id]`" x-model="item.product_id" @change="setProductPrice(item, $event.target)" required>
                                                <option value="">-- Pilih Produk --</option>
                                                @foreach($products as $product)
                                                    <option value="{{ $product->id }}" data-price="{{ $product->purchase_price }}">
                                                        {{ $product->name }} ({{ $product->unit }})
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <input type="number" class="form-control" :name="`items[${index}][qty]`" x-model.number="item.qty" min="1" required>
                                        </td>
                                        <td>
                                            <input type="number" class="form-control" :name="`items[${index}][price]`" x-model.number="item.price" min="0" required>
                                        </td>
                                        <td class="fw-medium">
                                            <span x-text="'Rp ' + formatRupiah((Number(item.qty) || 0) * (Number(item.price) || 0))"></span>
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
                        <textarea class="form-control" name="notes" rows="2" placeholder="Tambahkan catatan pengiriman...">{{ old('notes', $po->notes ?? '') }}</textarea>
                    </div>

                    <hr class="mb-4">

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('inventory.po.index') }}" class="btn btn-light border">Batal</a>
                        <button type="submit" class="btn btn-primary">{{ isset($po) ? 'Simpan Perubahan PO' : 'Simpan Purchase Order' }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function poForm() {
        let nextUid = 1;
        @php
            $initialPoItems = isset($po)
                ? $po->items->map(fn ($it) => ['product_id' => $it->product_id, 'qty' => (int) $it->qty, 'price' => (float) $it->price])->values()
                : [['product_id' => '', 'qty' => 1, 'price' => 0]];
        @endphp
        const rawItems = @json($initialPoItems);
        return {
            items: rawItems.map(it => ({ uid: nextUid++, product_id: it.product_id, qty: it.qty, price: it.price })),
            get grandTotal() {
                return this.items.reduce((total, item) => total + ((Number(item.qty) || 0) * (Number(item.price) || 0)), 0);
            },
            addItem() {
                this.items.push({ uid: nextUid++, product_id: '', qty: 1, price: 0 });
            },
            removeItem(index) {
                this.items.splice(index, 1);
            },
            setProductPrice(item, selectEl) {
                const selectedOption = selectEl.options[selectEl.selectedIndex];
                const price = selectedOption?.getAttribute('data-price');
                item.price = price ? parseFloat(price) : 0;
            },
            formatRupiah(value) {
                return new Intl.NumberFormat('id-ID').format(value || 0);
            }
        }
    }
</script>
@endpush
@endsection
