@extends('layouts.app')

@section('title', 'Beranda Driver')

@section('content')
<div class="page-header">
    <div>
        <h2 class="page-title">Workspace Driver &amp; Pengiriman</h2>
        <p class="page-subtitle">Petugas: <strong>{{ auth()->user()->name }}</strong> • {{ \Carbon\Carbon::now('Asia/Pontianak')->translatedFormat('l, d F Y') }}</p>
    </div>
    <a href="{{ route('fleet.jobs.index') }}" class="btn btn-dark">
        <i class="ph ph-map-pin"></i> Semua Perjalanan Armada
    </a>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-4">
        <div class="stat-card">
            <div class="stat-label">Tugas Sedang Berjalan</div>
            <div class="stat-value text-warning">{{ $activeJobs->count() }} Trip</div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="stat-card">
            <div class="stat-label">Selesai (Terakhir)</div>
            <div class="stat-value text-success">{{ $completedJobs->count() }} Trip</div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="stat-card">
            <div class="stat-label">Estimasi Komisi Bulan Ini</div>
            <div class="stat-value text-accent">Rp {{ number_format($totalCommissionMonth, 0, ',', '.') }}</div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header"><i class="ph ph-truck text-accent me-2"></i>Tugas Pengiriman Aktif Anda</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Tujuan</th>
                                <th>Armada</th>
                                <th>Berangkat</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($activeJobs as $job)
                                <tr>
                                    <td>
                                        <div class="fw-bold text-dark">{{ $job->destination }}</div>
                                        @if($job->notes)<small class="text-muted">{{ $job->notes }}</small>@endif
                                    </td>
                                    <td><span class="badge bg-dark">{{ $job->vehicle->plate_number ?? '-' }}</span></td>
                                    <td class="small">{{ $job->departure_date->format('d/m/Y H:i') }}</td>
                                    <td class="text-end">
                                        <a href="{{ route('fleet.jobs.waybill', $job->id) }}" class="btn btn-sm btn-light border me-1">
                                            <i class="ph ph-printer"></i> Surat Jalan
                                        </a>
                                        <form action="{{ route('fleet.jobs.complete', $job->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Tandai perjalanan ini sudah selesai?')">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success">Selesai</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center py-4 text-muted">Tidak ada tugas pengiriman aktif saat ini.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><i class="ph ph-fingerprint text-accent me-2"></i>Absensi Hari Ini</div>
            <div class="card-body p-4 text-center">
                @if(!$attendanceToday)
                    <p class="small text-muted mb-3">Anda belum absen masuk hari ini.</p>
                    <a href="{{ route('hr.attendance.index') }}" class="btn btn-primary w-100">
                        <i class="ph ph-qr-code"></i> Scan QR Absen Masuk
                    </a>
                @elseif(!$attendanceToday->clock_out)
                    <div class="badge bg-success bg-opacity-10 text-success px-3 py-2 mb-3">Masuk: {{ $attendanceToday->clock_in }} WIB</div>
                    <a href="{{ route('hr.attendance.index') }}" class="btn btn-outline-danger w-100">Scan QR Absen Pulang</a>
                @else
                    <div class="small text-success fw-bold">Absensi Selesai ({{ $attendanceToday->clock_in }} - {{ $attendanceToday->clock_out }})</div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
