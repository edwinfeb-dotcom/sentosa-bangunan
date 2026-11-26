@extends('layouts.app')

@section('title', 'Perjalanan Armada')

@section('content')
<div class="page-header">
    <div>
        <h2 class="page-title">Perjalanan Armada</h2>
        <p class="page-subtitle">Catat penugasan driver, ceklis kendaraan, biaya operasional, dan komisi perjalanan.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <button type="button" class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#importFleetModal">
            <i class="ph ph-upload-simple"></i> Import Excel Angkutan
        </button>
        <a href="{{ route('fleet.jobs.create') }}" class="btn btn-primary">
            <i class="ph ph-plus"></i> Buat Perjalanan
        </a>
    </div>
</div>

<!-- Ringkasan Angkutan -->
<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-label">Sedang Di Jalan</div>
            <div class="stat-value text-warning">{{ $summary['active_trips'] }} Trip</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-label">Total Pendapatan</div>
            <div class="stat-value">Rp {{ number_format($summary['total_revenue'], 0, ',', '.') }}</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-label">BBM &amp; Komisi Driver</div>
            <div class="stat-value text-danger">Rp {{ number_format($summary['total_operational'] + $summary['total_commission'], 0, ',', '.') }}</div>
            <small class="text-muted">Komisi: Rp {{ number_format($summary['total_commission'], 0, ',', '.') }}</small>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-label">Laba Bersih Angkutan</div>
            <div class="stat-value {{ $summary['net_profit'] >= 0 ? 'text-success' : 'text-danger' }}">
                Rp {{ number_format($summary['net_profit'], 0, ',', '.') }}
            </div>
        </div>
    </div>
</div>

<!-- Filter Perjalanan -->
<div class="card mb-4">
    <div class="card-body p-3">
        <form action="{{ route('fleet.jobs.index') }}" method="GET" class="row g-2 align-items-end">
            <div class="col-6 col-md-2">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">Semua</option>
                    <option value="on_trip" {{ request('status') === 'on_trip' ? 'selected' : '' }}>Di Jalan</option>
                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Selesai</option>
                </select>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label">Kendaraan</label>
                <select name="vehicle_id" class="form-select">
                    <option value="">Semua Armada</option>
                    @foreach($vehicles as $v)
                        <option value="{{ $v->id }}" {{ request('vehicle_id') == $v->id ? 'selected' : '' }}>{{ $v->plate_number }} - {{ $v->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label">Driver</label>
                <select name="driver_id" class="form-select">
                    <option value="">Semua Driver</option>
                    @foreach($drivers as $d)
                        <option value="{{ $d->id }}" {{ request('driver_id') == $d->id ? 'selected' : '' }}>{{ $d->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label">Dari Tanggal</label>
                <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-dark w-100">Filter</button>
                <a href="{{ route('fleet.jobs.index') }}" class="btn btn-light border">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Tujuan &amp; Ceklis</th>
                        <th>Kendaraan &amp; Driver</th>
                        <th>Tgl Berangkat</th>
                        <th>Pendapatan</th>
                        <th>Biaya / Komisi</th>
                        <th>Status</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($jobs as $job)
                    <tr>
                        <td>
                            <div class="fw-semibold text-dark">{{ $job->destination }}</div>
                            @if(!empty($job->checklist))
                                <div class="d-flex flex-wrap gap-1 mt-1">
                                    @foreach($job->checklist as $key => $ok)
                                        <span class="badge {{ $ok ? 'bg-success bg-opacity-10 text-success' : 'bg-danger bg-opacity-10 text-danger' }}" style="font-size: 0.65rem;">
                                            {{ strtoupper($key) }}: {{ $ok ? 'OK' : 'NO' }}
                                        </span>
                                    @endforeach
                                </div>
                            @endif
                            @if($job->notes)
                                <small class="text-muted d-block mt-1">{{ $job->notes }}</small>
                            @endif
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-dark">{{ $job->vehicle->plate_number ?? '-' }}</span>
                                <span class="text-secondary">{{ $job->driver->name ?? '-' }}</span>
                            </div>
                        </td>
                        <td class="small">{{ $job->departure_date->format('d/m/Y H:i') }}</td>
                        <td class="fw-semibold">Rp {{ number_format($job->revenue, 0, ',', '.') }}</td>
                        <td class="small">
                            <div>BBM/Tol: Rp {{ number_format($job->operational_cost, 0, ',', '.') }}</div>
                            <div class="text-accent">Komisi: Rp {{ number_format($job->driver_commission, 0, ',', '.') }}</div>
                        </td>
                        <td>
                            @if($job->status === 'on_trip')
                                <span class="badge bg-warning bg-opacity-10 text-warning">Di Jalan</span>
                            @else
                                <span class="badge bg-success bg-opacity-10 text-success">Selesai</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="d-inline-flex gap-1 align-items-center">
                                <a href="{{ route('fleet.jobs.waybill', $job->id) }}" class="btn btn-sm btn-light border" title="Cetak Surat Jalan">
                                    <i class="ph ph-printer"></i>
                                </a>
                                <a href="{{ route('fleet.jobs.edit', $job->id) }}" class="btn btn-sm btn-light border" title="Edit Perjalanan">
                                    <i class="ph ph-pencil-simple text-primary"></i>
                                </a>
                                @if($job->status === 'on_trip')
                                    <form action="{{ route('fleet.jobs.complete', $job->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Selesaikan perjalanan ini?')">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success">Selesai</button>
                                    </form>
                                @else
                                    <small class="text-muted me-1">{{ $job->return_date ? $job->return_date->format('d/m') : '-' }}</small>
                                @endif
                                <form action="{{ route('fleet.jobs.destroy', $job->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus catatan perjalanan ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-light border text-danger" title="Hapus">
                                        <i class="ph ph-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="ph ph-map-pin fs-1 d-block mb-2 opacity-50"></i>
                            Belum ada perjalanan armada.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($jobs->hasPages())
    <div class="card-footer bg-white">
        {{ $jobs->links() }}
    </div>
    @endif
</div>

<!-- Master Tarif Ongkos Kirim Armada -->
<div class="card mt-4">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span><i class="ph ph-currency-circle-dollar text-accent me-2"></i>Daftar Tarif Ongkos Angkutan ({{ ($rates ?? collect())->count() }} Rute)</span>
    </div>
    <div class="card-body p-4">
        <form action="{{ route('fleet.rates.store') }}" method="POST" class="row g-2 align-items-end mb-4">
            @csrf
            <div class="col-md-3">
                <label class="form-label small">Asal / Kuari</label>
                <input type="text" name="origin" class="form-control form-control-sm" value="SELABA" required>
            </div>
            <div class="col-md-3">
                <label class="form-label small">Tujuan</label>
                <input type="text" name="destination" class="form-control form-control-sm" placeholder="Contoh: Pasar Area / Ngabang" required>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small">Tarif 4 Kubik (Rp)</label>
                <input type="number" name="rate_4_m3" class="form-control form-control-sm tabular-nums" min="0" value="0" required>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small">Tarif 6 Kubik (Rp)</label>
                <input type="number" name="rate_6_m3" class="form-control form-control-sm tabular-nums" min="0" value="0" required>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-dark btn-sm w-100">Simpan Tarif</button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead>
                    <tr>
                        <th>Asal</th>
                        <th>Tujuan</th>
                        <th class="text-end">4 Kubik (m³)</th>
                        <th class="text-end">6 Kubik (m³)</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rates ?? [] as $rate)
                        <tr>
                            <td class="fw-semibold">{{ $rate->origin }}</td>
                            <td>{{ $rate->destination }}</td>
                            <td class="text-end tabular-nums">Rp {{ number_format($rate->rate_4_m3, 0, ',', '.') }}</td>
                            <td class="text-end tabular-nums">Rp {{ number_format($rate->rate_6_m3, 0, ',', '.') }}</td>
                            <td class="text-end">
                                <form action="{{ route('fleet.rates.destroy', $rate->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus tarif rute ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-light border text-danger py-0 px-2"><i class="ph ph-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center py-3 text-muted small">Belum ada tarif rute angkutan tersimpan. Silakan tambahkan atau import Excel Angkutan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Import Angkutan -->
<div class="modal fade" id="importFleetModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Import Excel Angkutan &amp; Tarif Rute</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('fleet.jobs.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body p-4">
                    <div class="alert alert-info border-0 small mb-3">
                        <div class="fw-bold mb-1">Mendukung File <code>ANGKUTAN (.xlsx)</code>:</div>
                        Otomatis membaca sheet <code>HARGA</code> (daftar ongkos 4 Kubik &amp; 6 Kubik) dan mendaftarkan armada toko (DAM, Strada, Kijang, Carry).
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Pilih File Excel (.xlsx, .xls, .xlsm, .csv)</label>
                        <input type="file" name="file" class="form-control" accept=".xlsx,.xlsm,.xls,.csv" required>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="import_trips" value="1" id="importFleetTrips">
                        <label class="form-check-label small" for="importFleetTrips">
                            Sekaligus import riwayat perjalanan dari sheet <code>DAM</code> &amp; <code>STRADA</code>
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success"><i class="ph ph-upload-simple"></i> Upload &amp; Import</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
