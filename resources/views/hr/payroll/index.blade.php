@extends('layouts.app')

@section('title', 'Payroll & Bonus Karyawan')

@section('content')
@php
    $bulanList = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
@endphp

<div class="page-header">
    <div>
        <h2 class="page-title">Payroll &amp; Bonus Karyawan</h2>
        <p class="page-subtitle">Atur gaji full bulanan karyawan, potong otomatis hari bolos (Alpha), tambah bonus manual, dan cetak slip gaji.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('hr.payroll.export', request()->query()) }}" class="btn btn-outline-success">
            <i class="ph ph-file-xls"></i> Export CSV
        </a>
        <button type="button" class="btn btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#salaryConfigPanel">
            <i class="ph ph-sliders"></i> Atur Gaji Full &amp; Potongan Bolos
        </button>
    </div>
</div>

<!-- Panel Pengaturan Gaji Full Bulanan oleh Owner -->
<div class="collapse mb-4" id="salaryConfigPanel">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="ph ph-money me-2 text-primary"></i>Pasang Gaji Full Bulanan &amp; Potongan Alpha per Karyawan</span>
            <small class="text-muted">Isi 0 pada Gaji Full jika karyawan dihitung per HOK harian</small>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table mb-0 align-middle">
                    <thead>
                        <tr>
                            <th>Karyawan</th>
                            <th>Role</th>
                            <th>Gaji Full Bulanan (Rp)</th>
                            <th>Potongan / Hari Bolos (Rp)</th>
                            <th>Gaji Harian / Lembur (Rp)</th>
                            <th class="text-end">Simpan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($employees as $emp)
                        <tr>
                            <td class="fw-semibold">{{ $emp->name }}</td>
                            <td class="text-capitalize">{{ $emp->role }}</td>
                            <td style="min-width: 170px;">
                                <input type="number" form="salary-config-{{ $emp->id }}" name="monthly_salary" class="form-control form-control-sm" value="{{ (float) ($emp->monthly_salary ?? 0) }}" min="0" required>
                            </td>
                            <td style="min-width: 170px;">
                                <input type="number" form="salary-config-{{ $emp->id }}" name="absence_deduction_per_day" class="form-control form-control-sm" value="{{ (float) ($emp->absence_deduction_per_day ?? 0) }}" min="0" placeholder="0 = Gaji Full / 26">
                            </td>
                            <td style="min-width: 150px;">
                                <input type="number" form="salary-config-{{ $emp->id }}" name="daily_salary" class="form-control form-control-sm" value="{{ (float) ($emp->daily_salary ?? 100000) }}" min="0">
                            </td>
                            <td class="text-end">
                                <form id="salary-config-{{ $emp->id }}" action="{{ route('hr.payroll.salary-config', $emp->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('PUT')
                                    <button type="submit" class="btn btn-sm btn-primary">Simpan</button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Form Generate -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body p-4">
                <div class="stat-icon bg-primary bg-opacity-10 text-primary mb-3">
                    <i class="ph ph-magic-wand"></i>
                </div>
                <h6 class="fw-bold mb-3">Generate Gaji Bulanan</h6>
                <form action="{{ route('hr.payroll.generate') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Pilih Bulan</label>
                        <select name="month" class="form-select" required>
                            @foreach($bulanList as $num => $namaBulan)
                                <option value="{{ $num }}" {{ date('n') == $num ? 'selected' : '' }}>{{ $namaBulan }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label">Tahun</label>
                            <input type="number" name="year" class="form-control" value="{{ date('Y') }}" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Hari Kerja</label>
                            <input type="number" name="working_days" class="form-control" value="26" min="1" max="31" required>
                        </div>
                    </div>
                    <div class="alert alert-light border small text-muted mb-3">
                        Jika <strong>Gaji Full Bulanan</strong> diisi, gaji dasar dimulai penuh dan otomatis dipotong untuk setiap hari <strong>Bolos / Alpha</strong> (sesuai rekap absensi).
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="ph ph-gear"></i> Generate / Hitung Ulang
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Tabel Riwayat -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <span><i class="ph ph-receipt me-2 text-primary"></i>Riwayat Slip Gaji</span>
                <form action="{{ route('hr.payroll.index') }}" method="GET" class="d-flex gap-2">
                    <select name="month" class="form-select form-select-sm" style="width: 130px;">
                        <option value="">Semua Bulan</option>
                        @foreach($bulanList as $num => $namaBulan)
                            <option value="{{ $num }}" {{ request('month') == $num ? 'selected' : '' }}>{{ $namaBulan }}</option>
                        @endforeach
                    </select>
                    <input type="number" name="year" class="form-control form-control-sm" value="{{ request('year') }}" placeholder="Tahun" style="width: 90px;">
                    <button type="submit" class="btn btn-sm btn-light border">Filter</button>
                </form>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Periode</th>
                                <th>Karyawan</th>
                                <th>Absensi</th>
                                <th>Gaji Pokok/Full</th>
                                <th>Bonus</th>
                                <th>Potongan</th>
                                <th>Gaji Bersih</th>
                                <th>Status</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($payrolls as $payroll)
                            <tr>
                                <td class="fw-semibold">{{ $bulanList[$payroll->month] ?? $payroll->month }} {{ $payroll->year }}</td>
                                <td>
                                    <div>{{ $payroll->user->name ?? '-' }}</div>
                                    @if(($payroll->monthly_salary_base ?? 0) > 0)
                                        <span class="badge bg-info bg-opacity-10 text-info">Gaji Full</span>
                                    @endif
                                </td>
                                <td class="small">
                                    <span class="text-success fw-semibold">{{ $payroll->present_days ?? 0 }} Hadir</span>
                                    @if(($payroll->alpha_days ?? 0) > 0)
                                        <div class="text-danger fw-semibold">{{ $payroll->alpha_days }} Bolos</div>
                                    @endif
                                </td>
                                <td>Rp {{ number_format($payroll->basic_salary, 0, ',', '.') }}</td>
                                <td class="text-success">+ Rp {{ number_format($payroll->allowance, 0, ',', '.') }}</td>
                                <td class="text-danger">
                                    <div>- Rp {{ number_format($payroll->deduction, 0, ',', '.') }}</div>
                                    @if(($payroll->alpha_deduction ?? 0) > 0)
                                        <small class="text-muted d-block">Alpha: Rp {{ number_format($payroll->alpha_deduction, 0, ',', '.') }}</small>
                                    @endif
                                </td>
                                <td class="fw-bold text-primary">Rp {{ number_format($payroll->net_salary, 0, ',', '.') }}</td>
                                <td>
                                    @if($payroll->status === 'paid')
                                        <span class="badge bg-success bg-opacity-10 text-success">Dibayar</span>
                                    @else
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary">Draft</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="d-inline-flex gap-1">
                                        @if($payroll->status === 'draft')
                                            <button type="button" class="btn btn-sm btn-light border text-success" data-bs-toggle="modal" data-bs-target="#bonusModal{{ $payroll->id }}" title="Tambah Bonus Manual">
                                                <i class="ph ph-plus-circle"></i> Bonus
                                            </button>
                                            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#payPayrollModal{{ $payroll->id }}" title="Bayar Gaji">
                                                Bayar
                                            </button>
                                        @endif
                                        <a href="{{ route('hr.payroll.slip', $payroll->id) }}" class="btn btn-sm btn-light border text-primary" title="Cetak Slip">
                                            <i class="ph ph-printer"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">
                                    <i class="ph ph-wallet fs-1 d-block mb-2 opacity-50"></i>
                                    Belum ada data payroll.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($payrolls->hasPages())
                <div class="card-footer bg-white">{{ $payrolls->links() }}</div>
            @endif
        </div>
    </div>
</div>

<!-- Modals rendered cleanly outside table -->
@foreach($payrolls as $payroll)
@if($payroll->status === 'draft')
<div class="modal fade" id="bonusModal{{ $payroll->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Tambah Bonus / Insentif: {{ $payroll->user->name ?? '-' }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('hr.payroll.bonus', $payroll->id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label">Nominal Bonus / Insentif (Rp)</label>
                        <input type="number" name="allowance" class="form-control form-control-lg" value="{{ $payroll->allowance }}" min="0" required>
                    </div>
                    <div>
                        <label class="form-label">Keterangan Bonus</label>
                        <input type="text" name="bonus_notes" class="form-control" value="{{ $payroll->bonus_notes }}" placeholder="Contoh: Bonus kerajinan / komisi target">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success">Simpan Bonus</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="payPayrollModal{{ $payroll->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Bayar Gaji: {{ $payroll->user->name ?? '-' }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('hr.payroll.pay', $payroll->id) }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="alert alert-info border-0 mb-3">
                        Total Gaji Bersih: <strong>Rp {{ number_format($payroll->net_salary, 0, ',', '.') }}</strong>
                    </div>
                    <div>
                        <label class="form-label">Sumber Dompet Pembayaran</label>
                        <select name="wallet_id" class="form-select">
                            @foreach($wallets as $wallet)
                                <option value="{{ $wallet->id }}">{{ $wallet->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Tandai Sudah Dibayar</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endforeach
@endsection
