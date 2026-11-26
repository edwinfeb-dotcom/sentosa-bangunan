@extends('layouts.app')

@section('title', 'Invoice Tagihan Proyek')

@section('content')
@php
    $used = (float) $project->total_material_cost;
    $paid = (float) ($project->paid_amount ?? 0);
    $remainingBill = max(0, $used - $paid);
@endphp
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
                        <div class="fw-bold fs-5 text-dark">INVOICE MATERIAL PROYEK</div>
                        <div class="small text-muted">Tgl Cetak: {{ date('d/m/Y') }}</div>
                    </div>
                </div>

                <div class="row g-3 mb-4 small">
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded border">
                            <div class="text-muted">Nama Proyek:</div>
                            <div class="fw-bold fs-6 text-dark">{{ $project->name }}</div>
                            <div>{{ $project->customer ? 'Kontraktor: ' . $project->customer->name : 'Proyek Internal Owner' }}</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded border">
                            <div class="d-flex justify-content-between"><span>Total Material:</span><strong>Rp {{ number_format($used, 0, ',', '.') }}</strong></div>
                            <div class="d-flex justify-content-between text-success"><span>Sudah Dibayar:</span><strong>Rp {{ number_format($paid, 0, ',', '.') }}</strong></div>
                            <div class="d-flex justify-content-between text-danger border-top pt-1 mt-1"><span>Sisa Tagihan:</span><strong>Rp {{ number_format($remainingBill, 0, ',', '.') }}</strong></div>
                        </div>
                    </div>
                </div>

                <table class="table table-bordered table-sm mb-4">
                    <thead class="table-light">
                        <tr>
                            <th>Tanggal</th>
                            <th>Material</th>
                            <th class="text-end">Qty</th>
                            <th class="text-end">Harga</th>
                            <th class="text-end">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($project->materials as $mat)
                        <tr>
                            <td class="small">{{ $mat->used_date->format('d/m/Y') }}</td>
                            <td>{{ $mat->product->name ?? '-' }}</td>
                            <td class="text-end">{{ $mat->qty }} {{ $mat->product->unit ?? '' }}</td>
                            <td class="text-end tabular-nums">Rp {{ number_format($mat->unit_price, 0, ',', '.') }}</td>
                            <td class="text-end fw-semibold tabular-nums">Rp {{ number_format($mat->subtotal, 0, ',', '.') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="d-flex justify-content-end gap-2 d-print-none">
                    <a href="{{ route('project.projects.show', $project->id) }}" class="btn btn-light border">Kembali</a>
                    <button type="button" onclick="window.print()" class="btn btn-primary">
                        <i class="ph ph-printer"></i> Cetak Invoice
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
