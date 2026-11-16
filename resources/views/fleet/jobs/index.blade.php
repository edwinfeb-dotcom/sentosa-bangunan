@extends('layouts.app')

@section('title', 'Perjalanan Armada')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1" style="color: #1E293B;">Perjalanan Armada</h4>
        <p class="text-muted mb-0" style="font-size: 0.875rem;">Catat penugasan driver, ceklis kendaraan, dan komisi.</p>
    </div>
    <a href="{{ route('fleet.jobs.create') }}" class="btn btn-primary d-flex align-items-center gap-2">
        <i class="ph ph-plus"></i> Buat Perjalanan
    </a>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert">
        <i class="ph ph-check-circle me-2"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Tujuan</th>
                        <th>Kendaraan & Driver</th>
                        <th>Tgl Berangkat</th>
                        <th>Pendapatan</th>
                        <th>Status</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($jobs as $job)
                    <tr>
                        <td class="fw-semibold" style="color: #1E293B;">{{ $job->destination }}</td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-dark">{{ $job->vehicle->plate_number }}</span>
                                <span class="text-muted">{{ $job->driver->name }}</span>
                            </div>
                        </td>
                        <td>{{ $job->departure_date->format('d/m/Y H:i') }}</td>
                        <td>Rp {{ number_format($job->revenue, 0, ',', '.') }}</td>
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
                        <td colspan="6" class="text-center py-5 text-muted">
                            <i class="ph ph-map-pin fs-1 d-block mb-2 text-secondary opacity-50"></i>
                            Belum ada perjalanan armada.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($jobs->hasPages())
    <div class="card-footer bg-white border-top pt-3">
        {{ $jobs->links() }}
    </div>
    @endif
</div>
@endsection