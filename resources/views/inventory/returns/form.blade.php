@extends('layouts.app')

@section('title', 'Buat Retur Supplier')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="d-flex align-items-center gap-3 mb-4">
            <a href="{{ route('inventory.returns.index') }}" class="btn btn-light border btn-sm">
                <i class="ph ph-arrow-left"></i>
            </a>
            <h4 class="fw-bold mb-0 text-dark">Buat Retur Barang ke Supplier</h4>
        </div>

        <div class="card">
            <div class="card-body p-4">
                <form action="{{ route('inventory.returns.store') }}" method="POST" x-data="returnForm()">
                    @csrf

                    <div class="row g-3 bg-light rounded p-3 mb-4">
                        <div class="col-md-3">
                            <label class="form-label">No. Retur</label>
                            <input type="text" name="return_number" class="form-control bg-white" value="{{ $returnNumber }}" readonly required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Tanggal Retur</label>
                            <input type="date" name="return_date" class="form-control bg-white" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Supplier</label>
                            <select name="supplier_id" class="form-select bg-white" required>
                                <option value="">-- Pilih Supplier --</option>
                                @foreach($suppliers as $s)
                                    <option value="{{ $s->id }}">{{ $s->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Referensi PO (Opsional)</label>
                            <select name="purchase_order_id" class="form-select bg-white">
                                <option value="">-- Tanpa PO --</option>
                                @foreach($purchaseOrders as $po)
                                    <option value="{{ $po->id }}">{{ $po->po_number }} ({{ $po->supplier->name ?? '' }})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">Metode Penyelesaian</label>
                            <select name="resolution" class="form-select" x-model="resolution" required>
                                <option value="cut_debt">Potong Hutang Nota Supplier (Bon)</option>
                                <option value="cash_refund">Refund Uang Masuk ke Kas</option>
                                <option value="exchange">Tukar Barang Baru</option>
                            </select>
                        </div>
                        <div class="col-md-6" x-show="resolution === 'cash_refund'">
                            <label class="form-label">Masuk ke Dompet</label>
                            <select name="wallet_id" class="form-select">
                                @foreach($wallets as $wallet)
                                    <option value="{{ $wallet->id }}">{{ $wallet->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="table-responsive mb-3">
                        <table class="table table-bordered align-middle">
                            <thead class="bg-light">
                                <tr>
                                    <th width="34%">Produk</th>
                                    <th width="14%">Qty Retur</th>
                                    <th width="18%">Harga Beli (Rp)</th>
                                    <th width="20%">Alasan Rusak/Retur</th>
                                    <th width="10%">Subtotal</th>
                                    <th width="4%"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="(item, index) in items" :key="item.uid">
                                    <tr>
                                        <td>
                                            <select class="form-select form-select-sm" :name="`items[${index}][product_id]`" x-model="item.product_id" @change="setProductPrice(item, $event.target)" required>
                                                <option value="">-- Pilih Produk --</option>
                                                @foreach($products as $product)
                                                    <option value="{{ $product->id }}" data-price="{{ $product->purchase_price }}">
                                                        {{ $product->name }} (Stok: {{ $product->current_stock }})
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <input type="number" class="form-control form-control-sm" :name="`items[${index}][qty]`" x-model.number="item.qty" min="1" required>
                                        </td>
                                        <td>
                                            <input type="number" class="form-control form-control-sm" :name="`items[${index}][price]`" x-model.number="item.price" min="0" required>
                                        </td>
                                        <td>
                                            <input type="text" class="form-control form-control-sm" :name="`items[${index}][reason]`" placeholder="Misal: Sak sobek">
                                        </td>
                                        <td class="fw-semibold small" x-text="'Rp ' + formatRupiah(item.qty * item.price)"></td>
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

                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <button type="button" class="btn btn-outline-primary btn-sm" @click="addItem()">
                            <i class="ph ph-plus"></i> Tambah Baris
                        </button>
                        <div class="fs-5">Total Nilai Retur: <strong class="text-primary" x-text="'Rp ' + formatRupiah(grandTotal)"></strong></div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Catatan Tambahan</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Opsional..."></textarea>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('inventory.returns.index') }}" class="btn btn-light border">Batal</a>
                        <button type="submit" class="btn btn-primary">Simpan Retur Supplier</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function returnForm() {
        let nextUid = 1;
        return {
            resolution: 'cut_debt',
            items: [{ uid: nextUid++, product_id: '', qty: 1, price: 0 }],
            get grandTotal() {
                return this.items.reduce((sum, i) => sum + ((Number(i.qty) || 0) * (Number(i.price) || 0)), 0);
            },
            addItem() {
                this.items.push({ uid: nextUid++, product_id: '', qty: 1, price: 0 });
            },
            removeItem(index) {
                this.items.splice(index, 1);
            },
            setProductPrice(item, selectEl) {
                const opt = selectEl.options[selectEl.selectedIndex];
                item.price = parseFloat(opt?.getAttribute('data-price')) || 0;
            },
            formatRupiah(val) {
                return new Intl.NumberFormat('id-ID').format(val || 0);
            }
        }
    }
</script>
@endpush
