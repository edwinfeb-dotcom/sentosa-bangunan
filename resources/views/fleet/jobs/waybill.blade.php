@extends('layouts.app')

@section('title', 'Surat Jalan Armada')

@section('content')
<div class="page-header d-print-none">
    <div class="d-flex align-items-center gap-3">
        <a href="{{ route('fleet.jobs.index') }}" class="btn btn-light border btn-sm"><i class="ph ph-arrow-left"></i></a>
        <div>
            <h2 class="page-title mb-0">Surat Jalan Pengiriman</h2>
            <p class="page-subtitle">SJ-{{ str_pad($job->id, 5, '0', STR_PAD_LEFT) }}</p>
        </div>
    </div>
    <button type="button" onclick="window.print()" class="btn btn-primary">
        <i class="ph ph-printer"></i> Cetak Surat Jalan
    </button>
</div>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body p-4 p-md-5">
                <div class="d-flex flex-wrap justify-content-between align-items-start border-bottom pb-3 mb-4 gap-3">
                    <div>
                        <h4 class="fw-bold text-dark mb-1">{{ $storeSetting->store_name }}</h4>
                        <div class="small text-secondary" style="max-width: 420px;">{{ $storeSetting->store_address }}</div>
                        @if($storeSetting->store_phone)
                            <div class="small text-muted">Telp/WA: {{ $storeSetting->store_phone }}</div>
                        @endif
                    </div>
                    <div class="text-md-end">
                        <div class="text-uppercase fw-bold text-accent small" style="letter-spacing: 0.08em;">Surat Jalan</div>
                        <div class="fs-5 fw-bold font-monospace">SJ-{{ str_pad($job->id, 5, '0', STR_PAD_LEFT) }}</div>
                        <div class="small text-muted">{{ $job->departure_date->format('d/m/Y H:i') }}</div>
                    </div>
                </div>

                <div class="row g-3 mb-4 small">
                    <div class="col-md-6">
                        <div class="p-3 rounded-3 border" style="background-color: var(--sb-surface-muted);">
                            <div class="text-muted mb-1">Tujuan / Penerima:</div>
                            <div class="fw-bold text-dark fs-6">{{ $job->destination }}</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 rounded-3 border" style="background-color: var(--sb-surface-muted);">
                            <div class="text-muted mb-1">Armada &amp; Driver:</div>
                            <div class="fw-bold text-dark">{{ $job->vehicle->plate_number ?? '-' }} — {{ $job->vehicle->name ?? '-' }}</div>
                            <div class="text-secondary">Driver: <strong>{{ $job->driver->name ?? '-' }}</strong></div>
                        </div>
                    </div>
                </div>

                @if(!empty($job->checklist))
                <div class="mb-4">
                    <div class="fw-bold small text-dark mb-2">Ceklis Keselamatan Kendaraan Sebelum Jalan:</div>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach($job->checklist as $key => $ok)
                            <span class="badge {{ $ok ? 'bg-success bg-opacity-10 text-success' : 'bg-danger bg-opacity-10 text-danger' }} border px-3 py-2">
                                {{ strtoupper($key) }}: {{ $ok ? 'LAYAK / OK' : 'PERLU CEK' }}
                            </span>
                        @endforeach
                    </div>
                </div>
                @endif

                <div class="mb-5 p-3 rounded-3 border">
                    <div class="small fw-bold text-dark mb-1">Catatan Muatan / Pengiriman:</div>
                    <div class="small text-secondary">{{ $job->notes ?: 'Muatan material sesuai faktur / pesanan.' }}</div>
                </div>

                <div class="row text-center pt-4 small">
                    <div class="col-4">
                        <div class="text-muted mb-5">Petugas Toko / Gudang</div>
                        <div class="border-top pt-1 fw-semibold mx-3">( ........................ )</div>
                    </div>
                    <div class="col-4">
                        <div class="text-muted mb-5">Driver Pengirim</div>
                        <div class="border-top pt-1 fw-semibold mx-3">{{ $job->driver->name ?? '( ........................ )' }}</div>
                    </div>
                    <div class="col-4">
                        <div class="text-muted mb-5">Penerima Barang</div>
                        <div class="border-top pt-1 fw-semibold mx-3">( ........................ )</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
