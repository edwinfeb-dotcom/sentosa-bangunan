@extends('layouts.app')

@section('title', 'Slip Gaji')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="d-flex align-items-center mb-4 d-print-none">
            <a href="{{ route('hr.payroll.index') }}" class="btn btn-light border btn-sm me-3">
                <i class="ph ph-arrow-left"></i>
            </a>
            <h4 class="fw-bold mb-0" style="color: #1E293B;">Slip Gaji Karyawan</h4>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-5">
                <div class="text-center mb-4 pb-4 border-bottom">
                    <!-- LOGO DI SINI -->
                    <img src="{{ asset('images/logo.png') }}" alt="Logo Sentosa" style="height: 60px; object-fit: contain;" class="mb-3">
                    <h4 class="fw-bold mb-1" style="color: #1E293B;">SENTOSA BANGUNAN</h4>
                    <p class="text-muted mb-0" style="font-size: 0.9rem;">Slip Gaji Karyawan</p>
                </div>

                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Nama Karyawan</span>
                    <span class="fw-bold" style="color: #1E293B;">{{ $payroll->user->name }}</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Periode Gaji</span>
                    <span class="fw-medium">{{ date('F', mktime(0, 0, 0, $payroll->month, 10)) }} {{ $payroll->year }}</span>
                </div>
                <div class="d-flex justify-content-between mb-4">
                    <span class="text-muted">Status</span>
                    <span class="badge bg-secondary bg-opacity-10 text-secondary text-capitalize">{{ $payroll->status }}</span>
                </div>

                <div class="bg-light rounded p-3 mb-4">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Gaji Pokok & Lembur</span>
                        <span class="fw-medium">Rp {{ number_format($payroll->basic_salary, 0, ',', '.') }}</span>
                    </div>
                    <div class="d-flex justify-content-between text-danger">
                        <span class="text-muted">Potongan (Utang/Kasbon)</span>
                        <span class="fw-medium">- Rp {{ number_format($payroll->deduction, 0, ',', '.') }}</span>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center bg-success bg-opacity-10 rounded p-3 mb-4">
                    <span class="fw-bold text-success">Gaji Bersih Diterima</span>
                    <span class="fs-5 fw-bold text-success">Rp {{ number_format($payroll->net_salary, 0, ',', '.') }}</span>
                </div>

                <div class="text-center text-muted mb-4" style="font-size: 0.75rem;">
                    Slip ini dibuat secara otomatis oleh sistem Sentosa Bangunan.
                </div>

                <div class="d-flex gap-2 d-print-none">
                    <a href="{{ route('hr.payroll.index') }}" class="btn btn-light border w-100">Kembali</a>
                    <button onclick="window.print()" class="btn btn-primary w-100 d-flex justify-content-center align-items-center gap-2">
                        <i class="ph ph-printer"></i> Cetak PDF
                    </button>
                </div>
                
                <div class="mt-3 text-center d-print-none">
                    <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $payroll->user->phone ?? '') }}?text=Halo%20{{ $payroll->user->name }},%20berikut%20slip%20gaji%20Anda%20bulan%20ini.%20Gaji%20bersih:%20Rp%20{{ number_format($payroll->net_salary, 0, ',', '.') }}" target="_blank" class="text-success text-decoration-none small fw-medium">
                        <i class="ph ph-whatsapp-logo fs-5 align-middle me-1"></i> Kirim ke WhatsApp Karyawan
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    @media print {
        body { background-color: white !important; }
        #sidebar-wrapper, .navbar, .d-print-none { display: none !important; }
        #page-content-wrapper { width: 100% !important; margin: 0 !important; padding: 0 !important; }
        .card { border: none !important; box-shadow: none !important; }
        .container-fluid { padding: 0 !important; }
        .col-md-6 { width: 100% !important; max-width: 100% !important; flex: 0 0 100% !important; }
    }
</style>
@endsection