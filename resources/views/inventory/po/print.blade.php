@extends('layouts.app')

@section('title', 'Cetak Purchase Order')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body p-4 p-md-5">
                <div class="d-flex justify-content-between align-items-start border-bottom pb-3 mb-4">
                    <div>
                        <h4 class="fw-bold mb-1">{{ $storeSetting->store_name ?? 'Sentosa Bangunan' }}</h4>
                        <div class="small text-muted">{{ $storeSetting->store_address ?? '' }}</div>
                        @if(!empty($storeSetting->store_phone))
                            <div class="small text-muted">Telp/WA: {{ $storeSetting->store_phone }}</div>
                        @endif
                    </div>
                    <div class="text-end">
                        <div class="fw-bold fs-5 text-dark">PURCHASE ORDER (PO)</div>
                        <div class="font-monospace fw-semibold text-primary">{{ $po->po_number }}</div>
                        <div class="small text-muted">Tanggal: {{ \Carbon\Carbon::parse($po->order_date)->format('d/m/Y') }}</div>
                    </div>
                </div>

                <div class="row g-3 mb-4 small">
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded border">
                            <div class="text-muted">Kepada Supplier:</div>
                            <div class="fw-bold fs-6 text-dark">{{ $po->supplier->name ?? '-' }}</div>
                            @if($po->supplier?->phone)<div>Telp: {{ $po->supplier->phone }}</div>@endif
                            @if($po->supplier?->address)<div>{{ $po->supplier->address }}</div>@endif
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded border">
                            <div class="text-muted">Status &amp; Pembuat:</div>
                            <div class="fw-semibold text-uppercase">{{ $po->status }}</div>
                            <div>Dibuat oleh: {{ $po->user->name ?? '-' }}</div>
                        </div>
                    </div>
                </div>

                <table class="table table-bordered table-sm mb-4">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>Nama Barang</th>
                            <th class="text-end">Qty Pesan</th>
                            <th class="text-end">Diterima</th>
                            <th class="text-end">Harga Satuan</th>
                            <th class="text-end">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($po->items as $idx => $item)
                        <tr>
                            <td>{{ $idx + 1 }}</td>
                            <td class="fw-medium">{{ $item->product->name ?? '-' }}</td>
                            <td class="text-end">{{ $item->qty }} {{ $item->product->unit ?? '' }}</td>
                            <td class="text-end">{{ $item->received_qty ?? 0 }}</td>
                            <td class="text-end tabular-nums">Rp {{ number_format($item->price, 0, ',', '.') }}</td>
                            <td class="text-end fw-semibold tabular-nums">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="5" class="text-end">Total Estimasi PO</th>
                            <th class="text-end fw-bold fs-6 tabular-nums">Rp {{ number_format($po->total_amount, 0, ',', '.') }}</th>
                        </tr>
                    </tfoot>
                </table>

                @if($po->notes)
                    <div class="small text-muted mb-4"><strong>Catatan:</strong> {{ $po->notes }}</div>
                @endif

                <div class="d-flex gap-2 justify-content-end d-print-none">
                    <a href="{{ route('inventory.po.index') }}" class="btn btn-light border">Kembali</a>
                    <button type="button" onclick="window.print()" class="btn btn-primary">
                        <i class="ph ph-printer"></i> Cetak PO
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
<style>
@media print {
    .app-sidebar, .app-topbar, .d-print-none { display: none !important; }
    .app-main, .page-content { margin: 0 !important; padding: 0 !important; }
    .card { border: none !important; box-shadow: none !important; }
}
</style>
@endsection
