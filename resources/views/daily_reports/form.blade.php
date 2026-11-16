@extends('layouts.app')

@section('title', 'Buat Laporan Harian')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="d-flex align-items-center mb-4">
            <a href="{{ route('daily-reports.index') }}" class="btn btn-light border btn-sm me-3">
                <i class="ph ph-arrow-left"></i>
            </a>
            <div>
                <h4 class="fw-bold mb-0" style="color: #1E293B;">Form Laporan Harian</h4>
                <p class="text-muted mb-0" style="font-size: 0.875rem;">Isi kotak putus-putus. Total transaksi, durasi kerja, HOK, dan lembur dihitung otomatis.</p>
            </div>
        </div>

        <form action="{{ route('daily-reports.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            
            <div class="card mb-4 border-0 shadow-sm" style="background-color: #FFFBEB;">
                <div class="card-body p-3 d-flex align-items-center gap-3">
                    <div class="bg-warning bg-opacity-25 text-warning rounded p-2 d-flex align-items-center justify-content-center">
                        <i class="ph ph-calendar-blank fs-4"></i>
                    </div>
                    <div class="flex-grow-1">
                        <label class="form-label fw-bold mb-1 text-warning-emphasis">Tanggal Laporan</label>
                        <input type="date" class="form-control border-warning" name="date" value="{{ date('Y-m-d') }}" required>
                    </div>
                </div>
            </div>

            <div class="accordion" id="reportAccordion">
                <!-- Step 1 -->
                <div class="accordion-item border-0 shadow-sm mb-2 rounded">
                    <h2 class="accordion-header">
                        <button class="accordion-button fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#step1">
                            <i class="ph ph-broom me-2 text-primary"></i> 1. Piket
                        </button>
                    </h2>
                    <div id="step1" class="accordion-collapse collapse show" data-bs-parent="#reportAccordion">
                        <div class="accordion-body">
                            <label class="form-label text-muted small">Kebersihan & Display</label>
                            <textarea class="form-control" name="piket" rows="2" placeholder="Contoh: Menyapu lantai, merapikan display semen..." required></textarea>
                        </div>
                    </div>
                </div>

                <!-- Step 2 -->
                <div class="accordion-item border-0 shadow-sm mb-2 rounded">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#step2">
                            <i class="ph ph-cash-register me-2 text-success"></i> 2. Laporan Kasir
                        </button>
                    </h2>
                    <div id="step2" class="accordion-collapse collapse" data-bs-parent="#reportAccordion">
                        <div class="accordion-body">
                            <label class="form-label text-muted small">Input kas, pengeluaran, total transaksi</label>
                            <textarea class="form-control" name="laporan_kasir" rows="2" placeholder="Contoh: Uang kecil 100rb, uang besar 500rb, total sistem 600rb..." required></textarea>
                        </div>
                    </div>
                </div>

                <!-- Step 3 -->
                <div class="accordion-item border-0 shadow-sm mb-2 rounded">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#step3">
                            <i class="ph ph-truck me-2 text-warning"></i> 3. Barang & Supplier
                        </button>
                    </h2>
                    <div id="step3" class="accordion-collapse collapse" data-bs-parent="#reportAccordion">
                        <div class="accordion-body">
                            <label class="form-label text-muted small">Pesanan, penerimaan, nota dari supplier</label>
                            <textarea class="form-control" name="barang_datang" rows="2" placeholder="Contoh: Semen 3 Roda datang 50 sak dari PT ABC..." required></textarea>
                        </div>
                    </div>
                </div>

                <!-- Step 4 -->
                <div class="accordion-item border-0 shadow-sm mb-2 rounded">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#step4">
                            <i class="ph ph-shield-check me-2 text-info"></i> 4. Pemeriksaan Aset
                        </button>
                    </h2>
                    <div id="step4" class="accordion-collapse collapse" data-bs-parent="#reportAccordion">
                        <div class="accordion-body">
                            <label class="form-label text-muted small">Kendaraan dan aset yang perlu dicek</label>
                            <textarea class="form-control" name="cek_aset" rows="2" placeholder="Contoh: Kendaraan aman, komputer kasir menyala normal..." required></textarea>
                        </div>
                    </div>
                </div>

                <!-- Step 5 -->
                <div class="accordion-item border-0 shadow-sm mb-2 rounded">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#step5">
                            <i class="ph ph-users me-2 text-danger"></i> 5. Absensi
                        </button>
                    </h2>
                    <div id="step5" class="accordion-collapse collapse" data-bs-parent="#reportAccordion">
                        <div class="accordion-body">
                            <label class="form-label text-muted small">Jam masuk, pulang, retase dan pinjaman</label>
                            <textarea class="form-control" name="absensi" rows="2" placeholder="Contoh: Hadir 4 orang, Lembur 1 jam..." required></textarea>
                        </div>
                    </div>
                </div>

                <!-- Step 6 -->
                <div class="accordion-item border-0 shadow-sm mb-2 rounded">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#step6">
                            <i class="ph ph-camera me-2 text-secondary"></i> 6. Dokumentasi Sore
                        </button>
                    </h2>
                    <div id="step6" class="accordion-collapse collapse" data-bs-parent="#reportAccordion">
                        <div class="accordion-body">
                            <label class="form-label text-muted small">Maksimal tiga foto beserta keterangannya</label>
                            <input type="file" class="form-control" name="foto" accept="image/*">
                        </div>
                    </div>
                </div>

                <!-- Step 7 -->
                <div class="accordion-item border-0 shadow-sm mb-2 rounded">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#step7">
                            <i class="ph ph-check-square-offset me-2 text-dark"></i> 7. Daftar Ceklis Sore
                        </button>
                    </h2>
                    <div id="step7" class="accordion-collapse collapse" data-bs-parent="#reportAccordion">
                        <div class="accordion-body">
                            <label class="form-label text-muted small">Standar penutupan toko dan catatan verify jika tidak</label>
                            <textarea class="form-control" name="ceklis_sore" rows="2" placeholder="Contoh: Pintu sudah dikunci, stok display dicek aman..." required></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Step 8 (Tombol Simpan) -->
            <div class="card border-0 shadow-sm mt-4 rounded" style="background-color: #F0FDF4;">
                <div class="card-body p-4 text-center">
                    <h5 class="fw-bold text-success mb-3"><i class="ph ph-paper-plane-right me-2"></i> 8. Simpan Laporan</h5>
                    <p class="text-muted small mb-4">Kumpulkan dan konfirmasi penyimpanan laporan harian ini.</p>
                    <div class="d-flex justify-content-center gap-2">
                        <a href="{{ route('daily-reports.index') }}" class="btn btn-light border">Batal</a>
                        <button type="submit" class="btn btn-success px-5">Simpan Laporan</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection