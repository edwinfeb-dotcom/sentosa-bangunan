@extends('layouts.app')

@section('title', 'Laporan Harian')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1" style="color: #1E293B;">Laporan Harian</h4>
        <p class="text-muted mb-0" style="font-size: 0.875rem;">Laporan operasional toko yang dikirim oleh kasir/admin.</p>
    </div>
    <a href="{{ route('daily-reports.create') }}" class="btn btn-primary d-flex align-items-center gap-2">
        <i class="ph ph-plus"></i> Buat Laporan
    </a>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert">
        <i class="ph ph-check-circle me-2"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert">
        <i class="ph ph-warning-circle me-2"></i> {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Petugas</th>
                        <th>Kode Verifikasi</th>
                        <th>Status</th>
                        <th class="text-end">Aksi / Verifikasi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reports as $report)
                    <tr>
                        <td class="fw-semibold" style="color: #1E293B;">{{ \Carbon\Carbon::parse($report->date)->format('d/m/Y') }}</td>
                        <td>{{ $report->user->name }}</td>
                        <td>
                            <span class="badge bg-secondary font-monospace fs-6 rounded">{{ $report->verification_code }}</span>
                        </td>
                        <td>
                            @if($report->status === 'verified')
                                <span class="badge bg-success bg-opacity-10 text-success">Terverifikasi</span>
                            @else
                                <span class="badge bg-warning bg-opacity-10 text-warning">Menunggu Verifikasi</span>
                            @endif
                        </td>
                        <td class="text-end">
                            @if($report->status === 'submitted' && in_array(auth()->user()->role, ['admin', 'owner']))
                                <!-- Form Verifikasi Inline -->
                                <form action="{{ route('daily-reports.verify', $report->id) }}" method="POST" class="d-flex gap-2 justify-content-end">
                                    @csrf
                                    <input type="text" name="code" class="form-control form-control-sm" placeholder="Masukkan Kode" required style="max-width: 130px;">
                                    <button type="submit" class="btn btn-sm btn-success">Verifikasi</button>
                                </form>
                            @elseif($report->status === 'verified')
                                <small class="text-muted">Oleh: {{ $report->verifier->name ?? '-' }}</small>
                            @else
                                <small class="text-muted">Tunggu Admin</small>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-5 text-muted">
                            <i class="ph ph-clipboard-text fs-1 d-block mb-2 text-secondary opacity-50"></i>
                            Belum ada laporan harian.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($reports->hasPages())
    <div class="card-footer bg-white border-top pt-3">
        {{ $reports->links() }}
    </div>
    @endif
</div>
@endsection