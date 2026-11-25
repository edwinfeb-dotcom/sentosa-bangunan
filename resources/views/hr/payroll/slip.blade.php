@extends('layouts.app')

@section('title', 'Slip Gaji')

@section('content')
@php
    $bulanList = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
@endphp
<div class="row justify-content-center">
    <div class="col-md-7 col-lg-6">
        <div class="d-flex align-items-center mb-4 d-print-none gap-3">
            <a href="{{ route('hr.payroll.index') }}" class="btn btn-light border btn-sm">
                <i class="ph ph-arrow-left"></i>
            </a>
            <h4 class="fw-bold mb-0 text-dark">Slip Gaji Karyawan</h4>
        </div>

        <div class="card">
            <div class="card-body p-4 p-md-5">
                <div class="text-center mb-4 pb-4 border-bottom">
                    <img src="{{ asset('images/logo.png') }}" alt="Logo Sentosa" style="height: 56px; object-fit: contain;" class="mb-2">
                    <h4 class="fw-bold mb-1 text-dark">SENTOSA BANGUNAN</h4>
                    <p class="text-muted mb-0 small">Slip Gaji Karyawan</p>
                </div>

                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Nama Karyawan</span>
                    <span class="fw-bold text-dark">{{ $payroll->user->name }}</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Periode Gaji</span>
                    <span class="fw-semibold">{{ $bulanList[$payroll->month] ?? $payroll->month }} {{ $payroll->year }}</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Rekap Kehadiran</span>
                    <span class="fw-semibold">{{ $payroll->present_days ?? 0 }} Hadir &middot; {{ $payroll->alpha_days ?? 0 }} Bolos/Alpha</span>
                </div>
                <div class="d-flex justify-content-between mb-4">
                    <span class="text-muted">Status</span>
                    <span class="badge {{ $payroll->status === 'paid' ? 'bg-success bg-opacity-10 text-success' : 'bg-secondary bg-opacity-10 text-secondary' }}">
                        {{ $payroll->status === 'paid' ? 'Sudah Dibayar' : 'Draft' }}
                    </span>
                </div>

                <div class="bg-light rounded p-3 mb-4">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">
                            {{ ($payroll->monthly_salary_base ?? 0) > 0 ? 'Gaji Full Bulanan & Lembur' : 'Gaji Pokok (HOK) & Lembur' }}
                        </span>
                        <span class="fw-semibold">Rp {{ number_format($payroll->basic_salary, 0, ',', '.') }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2 text-success">
                        <span>Bonus / Insentif {{ $payroll->bonus_notes ? '(' . $payroll->bonus_notes . ')' : '' }}</span>
                        <span class="fw-semibold">+ Rp {{ number_format($payroll->allowance, 0, ',', '.') }}</span>
                    </div>
                    @if(($payroll->alpha_deduction ?? 0) > 0)
                    <div class="d-flex justify-content-between mb-2 text-danger">
                        <span>Potongan Bolos / Alpha ({{ $payroll->alpha_days }} hari)</span>
                        <span class="fw-semibold">- Rp {{ number_format($payroll->alpha_deduction, 0, ',', '.') }}</span>
                    </div>
                    @endif
                    <div class="d-flex justify-content-between text-danger">
                        <span>Potongan Utang / Kasbon</span>
                        <span class="fw-semibold">- Rp {{ number_format(($payroll->debt_deduction ?? 0) > 0 ? $payroll->debt_deduction : max(0, $payroll->deduction - ($payroll->alpha_deduction ?? 0)), 0, ',', '.') }}</span>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center bg-success bg-opacity-10 rounded p-3 mb-4">
                    <span class="fw-bold text-success">Gaji Bersih Diterima</span>
                    <span class="fs-5 fw-bold text-success">Rp {{ number_format($payroll->net_salary, 0, ',', '.') }}</span>
                </div>

                <div class="text-center text-muted mb-4 small">
                    Slip ini dibuat secara otomatis oleh sistem Sentosa Bangunan.
                </div>

                <div class="d-flex gap-2 d-print-none">
                    <a href="{{ route('hr.payroll.index') }}" class="btn btn-light border w-100">Kembali</a>
                    <button onclick="window.print()" class="btn btn-primary w-100">
                        <i class="ph ph-printer"></i> Cetak PDF
                    </button>
                </div>

                <div class="mt-3 text-center d-print-none">
                    <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $payroll->user->phone ?? '') }}?text={{ urlencode('Halo ' . $payroll->user->name . ', berikut slip gaji Anda bulan ' . ($bulanList[$payroll->month] ?? $payroll->month) . ' ' . $payroll->year . '. Gaji bersih: Rp ' . number_format($payroll->net_salary, 0, ',', '.')) }}" target="_blank" class="text-success text-decoration-none small fw-semibold">
                        <i class="ph ph-whatsapp-logo fs-5 align-middle me-1"></i> Kirim ke WhatsApp Karyawan
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
