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

        <!-- Tampilkan Error Validasi Jika Ada yang Kosong -->
        @if ($errors->any())
            <div class="alert alert-danger mb-4">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('daily-reports.store') }}" method="POST" enctype="multipart/form-data" x-data="laporanKasir()">
            @csrf
            
            <div class="card mb-4 border-0 shadow-sm" style="background-color: #FFFBEB;">
                <div class="card-body p-3 d-flex align-items-center gap-3">
                    <div class="bg-warning bg-opacity-25 text-warning rounded p-2 d-flex align-items-center justify-content-center">
                        <i class="ph ph-calendar-blank fs-4"></i>
                    </div>
                    <div class="flex-grow-1">
                        <label class="form-label fw-bold mb-1 text-warning-emphasis">Tanggal Laporan</label>
                        <input type="date" class="form-control border-warning" name="date" value="{{ old('date', date('Y-m-d')) }}">
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
                            <textarea class="form-control" name="piket" rows="2" placeholder="Contoh: Menyapu lantai, merapikan display semen...">{{ old('piket') }}</textarea>
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
                        <div class="accordion-body bg-light">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label small fw-medium">Uang Kecil</label>
                                    <input type="number" class="form-control" name="uang_kecil" x-model.number="uangKecil" min="0" value="{{ old('uang_kecil', 0) }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-medium">Uang Besar</label>
                                    <input type="number" class="form-control" name="uang_besar" x-model.number="uangBesar" min="0" value="{{ old('uang_besar', 0) }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-medium">Transfer</label>
                                    <input type="number" class="form-control" name="transfer" x-model.number="transfer" min="0" value="{{ old('transfer', 0) }}">
                                </div>
                            </div>
                            <div class="mt-3 p-3 bg-success bg-opacity-10 rounded border border-success border-opacity-25 d-flex justify-content-between align-items-center">
                                <span class="fw-bold text-success">Total Kasir (Otomatis):</span>
                                <span class="fs-5 fw-bold text-success" x-text="'Rp ' + formatRupiah(totalKasir)"></span>
                                <!-- Hidden input untuk dikirim ke backend -->
                                <input type="hidden" name="total_kasir" x-model="totalKasir">
                            </div>
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
                            <textarea class="form-control" name="barang_datang" rows="2" placeholder="Contoh: Semen 3 Roda datang 50 sak dari PT ABC...">{{ old('barang_datang') }}</textarea>
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
                            <textarea class="form-control" name="cek_aset" rows="2" placeholder="Contoh: Kendaraan aman, komputer kasir menyala normal...">{{ old('cek_aset') }}</textarea>
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
                            <textarea class="form-control" name="absensi" rows="2" placeholder="Contoh: Hadir 4 orang, Lembur 1 jam...">{{ old('absensi') }}</textarea>
                        </div>
                    </div>
                </div>

                <!-- Step 6 -->
                <div class="accordion-item border-0 shadow-sm mb-2 rounded">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#step6">
                            <i class="ph ph-camera me-2 text-secondary"></i> 6. Dokumentasi Sore (Maks 3 Foto)
                        </button>
                    </h2>
                    <div id="step6" class="accordion-collapse collapse" data-bs-parent="#reportAccordion">
                        <div class="accordion-body">
                            <div class="row g-2">
                                <div class="col-md-4">
                                    <label class="form-label text-muted small">Foto 1</label>
                                    <input type="file" class="form-control form-control-sm" name="foto1" accept="image/*">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label text-muted small">Foto 2</label>
                                    <input type="file" class="form-control form-control-sm" name="foto2" accept="image/*">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label text-muted small">Foto 3</label>
                                    <input type="file" class="form-control form-control-sm" name="foto3" accept="image/*">
                                </div>
                            </div>
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
                            <textarea class="form-control" name="ceklis_sore" rows="2" placeholder="Contoh: Pintu sudah dikunci, stok display dicek aman...">{{ old('ceklis_sore') }}</textarea>
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

@push('scripts')
<script>
    function laporanKasir() {
        return {
            uangKecil: {{ old('uang_kecil', 0) }},
            uangBesar: {{ old('uang_besar', 0) }},
            transfer: {{ old('transfer', 0) }},
            get totalKasir() {
                return this.uangKecil + this.uangBesar + this.transfer;
            },
            formatRupiah(value) {
                return new Intl.NumberFormat('id-ID').format(value);
            }
        }
    }
</script>
@endpush
@endsection