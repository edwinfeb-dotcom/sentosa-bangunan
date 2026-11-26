@extends('layouts.app')

@section('title', 'Buat Laporan Harian')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-9">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div class="d-flex align-items-center gap-3">
                <a href="{{ route('daily-reports.index') }}" class="btn btn-light border btn-sm">
                    <i class="ph ph-arrow-left"></i>
                </a>
                <div>
                    <h4 class="fw-bold mb-0 text-dark">Form Laporan Harian (8 Langkah)</h4>
                    <p class="text-muted small mb-0">Lengkapi setiap bagian dan centang daftar tugas harian toko.</p>
                </div>
            </div>
            @if(in_array(auth()->user()->role, ['owner', 'admin']))
            <a href="{{ route('setting.checklists.index') }}" class="btn btn-light border btn-sm d-none d-sm-inline-flex">
                <i class="ph ph-gear"></i> Kelola Master Ceklis
            </a>
            @endif
        </div>

        <form action="{{ route('daily-reports.store') }}" method="POST" enctype="multipart/form-data" x-data="laporanKasir()">
            @csrf

            <div class="card mb-3 border-0 shadow-sm" style="background-color: #FFFBEB;">
                <div class="card-body p-3 d-flex flex-wrap align-items-center gap-3">
                    <div class="bg-warning bg-opacity-25 text-warning-emphasis rounded p-2 d-flex align-items-center justify-content-center">
                        <i class="ph ph-calendar-blank fs-4"></i>
                    </div>
                    <div class="flex-grow-1">
                        <label class="form-label fw-bold mb-1">Tanggal Laporan</label>
                        <input type="date" class="form-control border-warning" name="date" value="{{ old('date', date('Y-m-d')) }}" required>
                    </div>
                </div>
            </div>

            <div class="accordion" id="reportAccordion">
                <!-- Step 1 -->
                <div class="accordion-item border-0 shadow-sm mb-2 rounded overflow-hidden">
                    <h2 class="accordion-header">
                        <button class="accordion-button fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#step1">
                            <i class="ph ph-broom me-2 text-primary"></i> 1. Piket, Kebersihan & Display
                        </button>
                    </h2>
                    <div id="step1" class="accordion-collapse collapse show" data-bs-parent="#reportAccordion">
                        <div class="accordion-body">
                            @if(!empty($templates['piket']))
                                <div class="bg-light rounded p-3 mb-3">
                                    <div class="small fw-bold text-muted mb-2">Daftar Tugas Piket:</div>
                                    @foreach($templates['piket'] as $tpl)
                                        <div class="form-check mb-1">
                                            <input class="form-check-input" type="checkbox" name="checked_items[]" value="[Piket] {{ $tpl->title }}" id="tpl_piket_{{ $tpl->id }}">
                                            <label class="form-check-label small" for="tpl_piket_{{ $tpl->id }}">
                                                <strong>{{ $tpl->title }}</strong>
                                                @if($tpl->description) <span class="text-muted">— {{ $tpl->description }}</span> @endif
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                            <label class="form-label small text-muted">Catatan Piket & Display</label>
                            <textarea class="form-control" name="piket" rows="2" placeholder="Contoh: Lantai sudah disapu, display semen & cat rapi..." required>{{ old('piket') }}</textarea>
                        </div>
                    </div>
                </div>

                <!-- Step 2 -->
                <div class="accordion-item border-0 shadow-sm mb-2 rounded overflow-hidden">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#step2">
                            <i class="ph ph-cash-register me-2 text-success"></i> 2. Laporan Uang Kasir
                        </button>
                    </h2>
                    <div id="step2" class="accordion-collapse collapse" data-bs-parent="#reportAccordion">
                        <div class="accordion-body bg-light">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="small text-muted">Isi manual sesuai fisik kasir atau tarik otomatis dari POS hari ini:</span>
                                <button type="button" class="btn btn-sm btn-outline-primary" @click="fetchPosSummary()">
                                    <i class="ph ph-magic-wand"></i> Isi Otomatis dari POS
                                </button>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label small">Uang Kecil (Rp)</label>
                                    <input type="number" class="form-control" name="uang_kecil" x-model.number="uangKecil" min="0" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small">Uang Besar (Rp)</label>
                                    <input type="number" class="form-control" name="uang_besar" x-model.number="uangBesar" min="0" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small">Transfer (Rp)</label>
                                    <input type="number" class="form-control" name="transfer" x-model.number="transfer" min="0" required>
                                </div>
                            </div>
                            <div class="mt-3 p-3 bg-success bg-opacity-10 rounded border border-success border-opacity-25 d-flex justify-content-between align-items-center">
                                <span class="fw-bold text-success">Total Kasir (Otomatis):</span>
                                <span class="fs-5 fw-bold text-success" x-text="'Rp ' + formatRupiah(totalKasir)"></span>
                                <input type="hidden" name="total_kasir" :value="totalKasir">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 3 -->
                <div class="accordion-item border-0 shadow-sm mb-2 rounded overflow-hidden">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#step3">
                            <i class="ph ph-truck me-2 text-warning"></i> 3. Barang Datang & Supplier
                        </button>
                    </h2>
                    <div id="step3" class="accordion-collapse collapse" data-bs-parent="#reportAccordion">
                        <div class="accordion-body">
                            <label class="form-label small text-muted">Catatan pesanan, penerimaan barang & nota supplier</label>
                            <textarea class="form-control" name="barang_datang" rows="2" placeholder="Contoh: Semen 50 sak diterima dari PT ABC, atau Tidak ada barang datang..." required>{{ old('barang_datang') }}</textarea>
                        </div>
                    </div>
                </div>

                <!-- Step 4 -->
                <div class="accordion-item border-0 shadow-sm mb-2 rounded overflow-hidden">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#step4">
                            <i class="ph ph-shield-check me-2 text-info"></i> 4. Pemeriksaan Aset & Kendaraan
                        </button>
                    </h2>
                    <div id="step4" class="accordion-collapse collapse" data-bs-parent="#reportAccordion">
                        <div class="accordion-body">
                            @if(!empty($templates['cek_aset']))
                                <div class="bg-light rounded p-3 mb-3">
                                    <div class="small fw-bold text-muted mb-2">Ceklis Pemeriksaan Aset:</div>
                                    @foreach($templates['cek_aset'] as $tpl)
                                        <div class="form-check mb-1">
                                            <input class="form-check-input" type="checkbox" name="checked_items[]" value="[Aset] {{ $tpl->title }}" id="tpl_aset_{{ $tpl->id }}">
                                            <label class="form-check-label small" for="tpl_aset_{{ $tpl->id }}">
                                                <strong>{{ $tpl->title }}</strong>
                                                @if($tpl->description) <span class="text-muted">— {{ $tpl->description }}</span> @endif
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                            <label class="form-label small text-muted">Kondisi Kendaraan & Aset Toko</label>
                            <textarea class="form-control" name="cek_aset" rows="2" placeholder="Contoh: Komputer kasir, printer, dan truk aman..." required>{{ old('cek_aset') }}</textarea>
                        </div>
                    </div>
                </div>

                <!-- Step 5 -->
                <div class="accordion-item border-0 shadow-sm mb-2 rounded overflow-hidden">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#step5">
                            <i class="ph ph-users me-2 text-danger"></i> 5. Ringkasan Absensi & Lembur
                        </button>
                    </h2>
                    <div id="step5" class="accordion-collapse collapse" data-bs-parent="#reportAccordion">
                        <div class="accordion-body">
                            <label class="form-label small text-muted">Kehadiran, jam pulang, retase driver, atau catatan pinjaman</label>
                            <textarea class="form-control" name="absensi" rows="2" placeholder="Contoh: Hadir 4 karyawan, semua tepat waktu..." required>{{ old('absensi') }}</textarea>
                        </div>
                    </div>
                </div>

                <!-- Step 6 -->
                <div class="accordion-item border-0 shadow-sm mb-2 rounded overflow-hidden">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#step6">
                            <i class="ph ph-camera me-2 text-secondary"></i> 6. Dokumentasi Foto (Maks 3 Foto)
                        </button>
                    </h2>
                    <div id="step6" class="accordion-collapse collapse" data-bs-parent="#reportAccordion">
                        <div class="accordion-body">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label small">Foto 1</label>
                                    <input type="file" class="form-control form-control-sm" name="foto1" accept="image/*">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small">Foto 2</label>
                                    <input type="file" class="form-control form-control-sm" name="foto2" accept="image/*">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small">Foto 3</label>
                                    <input type="file" class="form-control form-control-sm" name="foto3" accept="image/*">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 7 -->
                <div class="accordion-item border-0 shadow-sm mb-2 rounded overflow-hidden">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#step7">
                            <i class="ph ph-check-square-offset me-2 text-dark"></i> 7. Daftar Ceklis Pagi & Sore
                        </button>
                    </h2>
                    <div id="step7" class="accordion-collapse collapse" data-bs-parent="#reportAccordion">
                        <div class="accordion-body">
                            @if(!empty($templates['ceklis_pagi']) || !empty($templates['ceklis_sore']))
                                <div class="row g-3 mb-3">
                                    @foreach(['ceklis_pagi' => 'Ceklis Pagi', 'ceklis_sore' => 'Ceklis Sore'] as $secKey => $secTitle)
                                        @if(!empty($templates[$secKey]))
                                            <div class="col-md-6">
                                                <div class="bg-light rounded p-3 h-100">
                                                    <div class="small fw-bold text-muted mb-2">{{ $secTitle }}:</div>
                                                    @foreach($templates[$secKey] as $tpl)
                                                        <div class="form-check mb-1">
                                                            <input class="form-check-input" type="checkbox" name="checked_items[]" value="[{{ $secTitle }}] {{ $tpl->title }}" id="tpl_{{ $secKey }}_{{ $tpl->id }}">
                                                            <label class="form-check-label small" for="tpl_{{ $secKey }}_{{ $tpl->id }}">
                                                                {{ $tpl->title }}
                                                            </label>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            @endif
                            <label class="form-label small text-muted">Catatan Standar Penutupan Toko</label>
                            <textarea class="form-control" name="ceklis_sore" rows="2" placeholder="Contoh: Rolling door terkunci, AC dan lampu sudah dimatikan..." required>{{ old('ceklis_sore') }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Step 8 -->
            <div class="card border-0 shadow-sm mt-4" style="background-color: #F0FDF4;">
                <div class="card-body p-4 text-center">
                    <h5 class="fw-bold text-success mb-2"><i class="ph ph-paper-plane-right me-2"></i>8. Kirim Laporan Harian</h5>
                    <p class="text-muted small mb-4">Setelah disimpan, sistem menghasilkan kode verifikasi unik untuk validasi Admin/Owner.</p>
                    <div class="d-flex justify-content-center gap-2">
                        <a href="{{ route('daily-reports.index') }}" class="btn btn-light border">Batal</a>
                        <button type="submit" class="btn btn-success px-4">Simpan & Kirim Laporan</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function laporanKasir() {
        return {
            uangKecil: {{ old('uang_kecil', 0) }},
            uangBesar: {{ old('uang_besar', 0) }},
            transfer: {{ old('transfer', 0) }},
            get totalKasir() {
                return (Number(this.uangKecil) || 0) + (Number(this.uangBesar) || 0) + (Number(this.transfer) || 0);
            },
            formatRupiah(value) {
                return new Intl.NumberFormat('id-ID').format(value || 0);
            },
            async fetchPosSummary() {
                const dateEl = document.querySelector('input[name="date"]');
                const d = dateEl ? dateEl.value : '';
                try {
                    const res = await fetch(`{{ route('daily-reports.pos-summary') }}?date=${encodeURIComponent(d)}`, {
                        headers: { 'Accept': 'application/json' }
                    });
                    if (!res.ok) return;
                    const data = await res.json();
                    this.uangKecil = Number(data.uang_kecil || 0);
                    this.uangBesar = Number(data.uang_besar || 0);
                    this.transfer = Number(data.transfer || 0);
                } catch (e) {}
            }
        }
    }
</script>
@endpush
