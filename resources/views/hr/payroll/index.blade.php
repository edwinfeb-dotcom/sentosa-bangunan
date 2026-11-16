@extends('layouts.app')

@section('title', 'Payroll Karyawan')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1" style="color: #1E293B;">Payroll Karyawan</h4>
        <p class="text-muted mb-0" style="font-size: 0.875rem;">Generate dan kelola slip gaji bulanan karyawan.</p>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert">
        <i class="ph ph-check-circle me-2"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="row g-4">
    <!-- Form Generate -->
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <div class="bg-primary bg-opacity-10 text-primary rounded p-2 d-inline-flex mb-3">
                    <i class="ph ph-magic-wand fs-4"></i>
                </div>
                <h6 class="fw-bold mb-3" style="color: #1E293B;">Generate Gaji Baru</h6>
                <form action="{{ route('hr.payroll.generate') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label text-muted small">Pilih Bulan</label>
                        <select name="month" class="form-select" required>
                            @for($i=1; $i<=12; $i++)
                                <option value="{{ $i }}" {{ date('n') == $i ? 'selected' : '' }}>{{ date('F', mktime(0, 0, 0, $i, 10)) }}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="form-label text-muted small">Pilih Tahun</label>
                        <input type="number" name="year" class="form-control" value="{{ date('Y') }}" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 d-flex justify-content-center align-items-center gap-2">
                        <i class="ph ph-gear"></i> Generate Slip Gaji
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Tabel Riwayat -->
    <div class="col-md-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom pt-3 pb-3">
                <h6 class="fw-bold mb-0" style="color: #1E293B;"><i class="ph ph-receipt me-2"></i> Riwayat Payroll</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Periode</th>
                                <th>Nama Karyawan</th>
                                <th>Gaji Bersih</th>
                                <th>Status</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($payrolls as $payroll)
                            <tr>
                                <td class="fw-medium" style="color: #1E293B;">{{ date('F', mktime(0, 0, 0, $payroll->month, 10)) }} {{ $payroll->year }}</td>
                                <td>{{ $payroll->user->name }}</td>
                                <td class="fw-bold text-primary">Rp {{ number_format($payroll->net_salary, 0, ',', '.') }}</td>
                                <td>
                                    @if($payroll->status === 'paid')
                                        <span class="badge bg-success bg-opacity-10 text-success">Dibayar</span>
                                    @else
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary">Draft</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('hr.payroll.slip', $payroll->id) }}" class="btn btn-sm btn-light border text-primary">
                                        <i class="ph ph-printer me-1"></i> Slip
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <i class="ph ph-wallet fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                    Belum ada data payroll.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection