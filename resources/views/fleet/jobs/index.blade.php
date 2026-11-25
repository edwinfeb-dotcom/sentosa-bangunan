@extends('layouts.app')

@section('title', 'Perjalanan Armada')

@section('content')
<div class="page-header">
    <div>
        <h2 class="page-title">Perjalanan Armada</h2>
        <p class="page-subtitle">Catat penugasan driver, ceklis kendaraan, biaya operasional, dan komisi perjalanan.</p>
    </div>
    <a href="{{ route('fleet.jobs.create') }}" class="btn btn-primary">
        <i class="ph ph-plus"></i> Buat Perjalanan
    </a>
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
                            @if($job->status === 'on_trip')
                                <form action="{{ route('fleet.jobs.complete', $job->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Selesaikan perjalanan ini?')">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-success">Selesai</button>
                                </form>
                            @else
                                <small class="text-muted">Kembali: {{ $job->return_date ? $job->return_date->format('d/m H:i') : '-' }}</small>
                            @endif
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
@endsection
