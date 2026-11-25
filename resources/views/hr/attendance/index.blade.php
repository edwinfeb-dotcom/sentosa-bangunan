@extends('layouts.app')

@section('title', 'Absensi & Scan QR Karyawan')

@section('content')
@php
    $bulanList = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
    $statusBadgeMap = [
        'hadir' => 'bg-success bg-opacity-10 text-success',
        'izin'  => 'bg-info bg-opacity-10 text-info',
        'sakit' => 'bg-warning bg-opacity-10 text-warning',
        'alpha' => 'bg-danger bg-opacity-10 text-danger',
    ];
@endphp

<div class="page-header">
    <div>
        <h2 class="page-title">Absensi &amp; Scan QR Karyawan</h2>
        <p class="page-subtitle">Scan QR toko untuk absen masuk/pulang otomatis, pantau kehadiran harian, dan rekap bulanan rapi.</p>
    </div>
    @if($permanentQrSession)
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('hr.attendance.qr-kiosk', ['mode' => 'permanent']) }}" class="btn btn-primary">
            <i class="ph ph-printer"></i> Cetak Poster QR Absensi
        </a>
    </div>
    @endif
</div>

<div class="row g-4 mb-4">
    <!-- Scan QR & Clock In/Out Karyawan -->
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-body p-4 text-center d-flex flex-column align-items-center justify-content-center">
                <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center mb-3" style="width: 72px; height: 72px;">
                    <i class="ph ph-qr-code fs-1"></i>
                </div>
                <h5 class="fw-bold mb-1">Absensi Hari Ini</h5>
                <p class="text-muted small mb-2">{{ \Carbon\Carbon::now('Asia/Pontianak')->translatedFormat('l, d F Y') }}</p>
                <div class="fs-2 fw-bold text-primary mb-3 font-monospace" id="jam-sekarang">
                    {{ \Carbon\Carbon::now('Asia/Pontianak')->format('H:i:s') }}
                </div>

                @if(!$attendanceToday || ($attendanceToday->status === 'hadir' && !$attendanceToday->clock_out))
                    <button type="button" class="btn btn-success btn-lg w-100 mb-3" data-bs-toggle="modal" data-bs-target="#qrScannerModal">
                        <i class="ph ph-camera"></i>
                        {{ !$attendanceToday ? 'Scan QR Absen Masuk' : 'Scan QR Absen Pulang' }}
                    </button>
                @endif

                @if(!$attendanceToday)
                    <form action="{{ route('hr.attendance.clock-in') }}" method="POST" class="w-100">
                        @csrf
                        <button type="submit" class="btn btn-outline-primary w-100">
                            <i class="ph ph-sign-in"></i> Clock In Manual (Tanpa QR)
                        </button>
                    </form>
                @elseif($attendanceToday->status !== 'hadir')
                    <div class="alert alert-warning w-100 mb-0 small">
                        Status Anda hari ini tercatat: <strong class="text-uppercase">{{ $attendanceToday->status }}</strong>
                    </div>
                @elseif(!$attendanceToday->clock_out)
                    <div class="bg-light rounded p-2 w-100 mb-3 d-flex justify-content-between px-3 small">
                        <span class="text-muted">Jam Masuk ({{ strtoupper($attendanceToday->check_in_method ?? 'MANUAL') }}):</span>
                        <span class="fw-bold text-success">{{ $attendanceToday->clock_in }}</span>
                    </div>
                    <form action="{{ route('hr.attendance.clock-out') }}" method="POST" class="w-100">
                        @csrf
                        <button type="submit" class="btn btn-outline-danger w-100">
                            <i class="ph ph-sign-out"></i> Clock Out Manual
                        </button>
                    </form>
                @else
                    <div class="bg-success bg-opacity-10 rounded p-3 w-100 text-success">
                        <div class="fw-bold mb-1">Absensi Hari Ini Selesai</div>
                        <div class="d-flex justify-content-between small">
                            <span>Masuk: {{ $attendanceToday->clock_in }}</span>
                            <span>Pulang: {{ $attendanceToday->clock_out }}</span>
                        </div>
                        <div class="small mt-1">
                            Metode: <span class="badge bg-white text-success border">{{ strtoupper($attendanceToday->check_in_method ?? 'MANUAL') }}</span>
                            &middot; HOK: <strong>{{ $attendanceToday->hok }}</strong>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Riwayat Pribadi -->
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header"><i class="ph ph-clock-counter-clockwise me-2 text-primary"></i>Riwayat Absensi Anda</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Status</th>
                                <th>Masuk</th>
                                <th>Pulang</th>
                                <th>Metode</th>
                                <th>HOK</th>
                                <th>Lembur</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($history as $row)
                            <tr>
                                <td class="fw-semibold">{{ $row->date->format('d/m/Y') }}</td>
                                <td>
                                    <span class="badge {{ $statusBadgeMap[$row->status ?? 'hadir'] ?? 'bg-secondary' }} text-uppercase">
                                        {{ $row->status === 'alpha' ? 'Bolos / Alpha' : ($row->status ?? 'hadir') }}
                                    </span>
                                </td>
                                <td><span class="badge bg-success bg-opacity-10 text-success">{{ $row->clock_in ?? '-' }}</span></td>
                                <td><span class="badge bg-danger bg-opacity-10 text-danger">{{ $row->clock_out ?? '-' }}</span></td>
                                <td>
                                    @if(str_contains((string) $row->check_in_method, 'qr'))
                                        <span class="badge bg-success bg-opacity-10 text-success"><i class="ph ph-qr-code me-1"></i>QR</span>
                                    @else
                                        <span class="badge bg-light text-dark border text-uppercase">{{ $row->check_in_method ?? 'MANUAL' }}</span>
                                    @endif
                                </td>
                                <td><span class="badge bg-primary bg-opacity-10 text-primary">{{ $row->hok }} HOK</span></td>
                                <td>
                                    @if($row->overtime_hours > 0)
                                        <span class="badge bg-warning bg-opacity-10 text-warning">{{ $row->overtime_hours }} Jam</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">Belum ada data absensi.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($history->hasPages())
                <div class="card-footer bg-white">{{ $history->links() }}</div>
            @endif
        </div>
    </div>
</div>

@if($teamAttendances !== null)
<!-- Panel Khusus Owner & Admin: QR Harian + Rekap Bulanan Rapi + Pantauan Harian -->
<div class="row g-4 mb-4">
    <!-- QR Toko Tempel Dinding (Permanen) -->
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="ph ph-qr-code me-2 text-primary"></i>QR Poster Tempel Toko</span>
                <a href="{{ route('hr.attendance.qr-kiosk', ['mode' => 'permanent']) }}" class="btn btn-sm btn-primary">
                    <i class="ph ph-printer"></i> Cetak Poster
                </a>
            </div>
            <div class="card-body p-4 text-center">
                <span class="badge bg-success bg-opacity-10 text-success mb-2">Berlaku Setiap Hari (Permanen)</span>
                <div>
                    <div class="d-inline-block p-2 border rounded bg-white mb-2">
                        <canvas id="admin-qr-preview" data-token="{{ $permanentQrSession->token }}"></canvas>
                    </div>
                </div>
                <div class="font-monospace small fw-semibold text-dark mb-2">{{ $permanentQrSession->token }}</div>
                <p class="text-muted small mb-3">Cetak &amp; tempel poster QR ini di pintu masuk/area toko agar karyawan bisa absen langsung tanpa bolak-balik ke ruang admin.</p>
                <div class="d-flex gap-2">
                    <a href="{{ route('hr.attendance.qr-kiosk', ['mode' => 'permanent']) }}" class="btn btn-sm btn-primary w-100">
                        <i class="ph ph-printer"></i> Cetak / Download PNG
                    </a>
                    <form action="{{ route('hr.attendance.qr-refresh') }}" method="POST" onsubmit="return confirm('Reset Poster QR? QR yang sudah dicetak sebelumnya akan berhenti berlaku.')">
                        @csrf
                        <input type="hidden" name="type" value="permanent">
                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Reset QR Poster">
                            <i class="ph ph-arrows-clockwise"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Rekap Bulanan Rapi (Owner & Admin) -->
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <span><i class="ph ph-calendar-check me-2 text-primary"></i>Rekap Absensi Bulanan ({{ $bulanList[$recapMonth] ?? $recapMonth }} {{ $recapYear }})</span>
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <form action="{{ route('hr.attendance.index') }}" method="GET" class="d-flex gap-1 align-items-center">
                        <input type="hidden" name="date" value="{{ $filterDate }}">
                        <select name="month" class="form-select form-select-sm" style="width: 120px;">
                            @foreach($bulanList as $num => $nama)
                                <option value="{{ $num }}" {{ $recapMonth === $num ? 'selected' : '' }}>{{ $nama }}</option>
                            @endforeach
                        </select>
                        <input type="number" name="year" class="form-control form-control-sm" value="{{ $recapYear }}" style="width: 80px;">
                        <button type="submit" class="btn btn-sm btn-light border">Lihat</button>
                    </form>
                    <a href="{{ route('hr.attendance.export-monthly', ['month' => $recapMonth, 'year' => $recapYear]) }}" class="btn btn-sm btn-success">
                        <i class="ph ph-file-xls"></i> Export Rekap (.xlsx)
                    </a>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th>Karyawan</th>
                                <th class="text-center">Hadir</th>
                                <th class="text-center">Izin</th>
                                <th class="text-center">Sakit</th>
                                <th class="text-center">Bolos / Alpha</th>
                                <th class="text-center">Total HOK</th>
                                <th class="text-center">QR Scan</th>
                                <th class="text-end">Gaji Full</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($monthlySummary as $item)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $item['user']->name }}</div>
                                    <small class="text-muted text-capitalize">{{ $item['user']->role }}</small>
                                </td>
                                <td class="text-center"><span class="badge bg-success bg-opacity-10 text-success">{{ $item['hadir_days'] }} hr</span></td>
                                <td class="text-center"><span class="badge bg-info bg-opacity-10 text-info">{{ $item['izin_days'] }} hr</span></td>
                                <td class="text-center"><span class="badge bg-warning bg-opacity-10 text-warning">{{ $item['sakit_days'] }} hr</span></td>
                                <td class="text-center"><span class="badge bg-danger bg-opacity-10 text-danger">{{ $item['alpha_days'] }} hr</span></td>
                                <td class="text-center fw-semibold">{{ number_format($item['total_hok'], 2) }}</td>
                                <td class="text-center">{{ $item['qr_count'] }}x</td>
                                <td class="text-end">
                                    @if(($item['user']->monthly_salary ?? 0) > 0)
                                        <span class="fw-semibold text-primary">Rp {{ number_format($item['user']->monthly_salary, 0, ',', '.') }}</span>
                                    @else
                                        <span class="text-muted small">Harian (Rp {{ number_format($item['user']->daily_salary ?? 100000, 0, ',', '.') }})</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="8" class="text-center py-4 text-muted">Belum ada data karyawan.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
                <small class="text-muted">Tandai otomatis hari kerja lampau (Senin–Sabtu) tanpa absen sebagai Alpha agar terhitung saat generate gaji:</small>
                <form action="{{ route('hr.attendance.sync-alpha') }}" method="POST" onsubmit="return confirm('Tandai hari kerja lampau yang kosong sebagai Alpha / Bolos untuk bulan terpilih?')">
                    @csrf
                    <input type="hidden" name="month" value="{{ $recapMonth }}">
                    <input type="hidden" name="year" value="{{ $recapYear }}">
                    <button type="submit" class="btn btn-sm btn-outline-danger">
                        <i class="ph ph-user-minus"></i> Sinkronisasi Hari Bolos (Alpha)
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Pantauan Harian & Input Status -->
<div class="card">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span><i class="ph ph-users me-2 text-primary"></i>Pantauan Absensi Harian ({{ \Carbon\Carbon::parse($filterDate)->format('d/m/Y') }})</span>
        <div class="d-flex flex-wrap gap-2 align-items-center">
            <form action="{{ route('hr.attendance.index') }}" method="GET" class="d-flex gap-2 align-items-center">
                <input type="hidden" name="month" value="{{ $recapMonth }}">
                <input type="hidden" name="year" value="{{ $recapYear }}">
                <input type="date" name="date" class="form-control form-control-sm" value="{{ $filterDate }}" onchange="this.form.submit()">
            </form>
            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#markStatusModal">
                <i class="ph ph-pencil-simple"></i> Catat Status / Bolos
            </button>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Karyawan</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Metode</th>
                        <th>Jam Masuk</th>
                        <th>Jam Pulang</th>
                        <th>HOK</th>
                        <th>Lembur</th>
                        <th>Catatan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($teamAttendances as $att)
                        <tr>
                            <td class="fw-semibold">{{ $att->user->name ?? '-' }}</td>
                            <td class="text-capitalize">{{ $att->user->role ?? '-' }}</td>
                            <td>
                                <span class="badge {{ $statusBadgeMap[$att->status ?? 'hadir'] ?? 'bg-secondary' }} text-uppercase">
                                    {{ $att->status === 'alpha' ? 'Bolos / Alpha' : ($att->status ?? 'hadir') }}
                                </span>
                            </td>
                            <td>
                                @if(str_contains((string) $att->check_in_method, 'qr'))
                                    <span class="badge bg-success bg-opacity-10 text-success"><i class="ph ph-qr-code me-1"></i>QR</span>
                                @else
                                    <span class="badge bg-light text-dark border text-uppercase">{{ $att->check_in_method ?? 'MANUAL' }}</span>
                                @endif
                            </td>
                            <td>{{ $att->clock_in ?? '-' }}</td>
                            <td>{{ $att->clock_out ?? ($att->status === 'hadir' ? 'Belum Pulang' : '-') }}</td>
                            <td>{{ $att->hok }}</td>
                            <td>{{ $att->overtime_hours }} Jam</td>
                            <td class="text-muted small">{{ $att->notes ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-center py-4 text-muted">Belum ada data absensi pada tanggal ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

<!-- Modal Scanner QR Karyawan -->
<div class="modal fade" id="qrScannerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="ph ph-qr-code text-primary me-2"></i>Scan QR Code Absensi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div id="qr-camera-feedback" class="alert d-none small mb-3"></div>

                <div class="border rounded bg-dark text-white text-center overflow-hidden mb-3" style="min-height: 240px;">
                    <div id="qr-reader" style="width: 100%;"></div>
                    <div id="qr-camera-placeholder" class="p-4 small text-light opacity-75">
                        <i class="ph ph-camera fs-1 d-block mb-2"></i>
                        Klik tombol di bawah untuk mengaktifkan kamera, atau scan / ketik kode QR toko di kolom input.
                    </div>
                </div>

                <div class="d-flex gap-2 mb-3">
                    <button type="button" id="btn-start-qr-camera" class="btn btn-primary w-100">
                        <i class="ph ph-camera"></i> Aktifkan Kamera Scan QR
                    </button>
                    <button type="button" id="btn-stop-qr-camera" class="btn btn-outline-secondary d-none">
                        Matikan
                    </button>
                </div>

                <hr>

                <form action="{{ route('hr.attendance.scan-qr') }}" method="POST" id="qr-manual-form">
                    @csrf
                    <label class="form-label small fw-semibold">Atau Input / Scan Barcode Scanner USB:</label>
                    <div class="input-group">
                        <input type="text" name="qr_token" id="qr_token_input" class="form-control font-monospace" placeholder="SB-ABSEN-..." required>
                        <button type="submit" class="btn btn-success">
                            <i class="ph ph-check-circle"></i> Submit QR
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@if($teamAttendances !== null)
<!-- Modal Catat / Koreksi Status Absensi (Owner / Admin) -->
<div class="modal fade" id="markStatusModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Catat Status Absensi / Bolos Karyawan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('hr.attendance.mark-status') }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label">Pilih Karyawan</label>
                        <select name="user_id" class="form-select" required>
                            @foreach($staffUsers as $staff)
                                <option value="{{ $staff->id }}">{{ $staff->name }} ({{ strtoupper($staff->role) }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label">Tanggal</label>
                            <input type="date" name="date" class="form-control" value="{{ $filterDate }}" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Status Kehadiran</label>
                            <select name="status" class="form-select" required>
                                <option value="alpha">Bolos / Alpha (Kena Potongan)</option>
                                <option value="izin">Izin</option>
                                <option value="sakit">Sakit</option>
                                <option value="hadir">Hadir</option>
                            </select>
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label">Jam Masuk (Opsional Hadir)</label>
                            <input type="time" name="clock_in" class="form-control">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Jam Pulang (Opsional Hadir)</label>
                            <input type="time" name="clock_out" class="form-control">
                        </div>
                    </div>
                    <div>
                        <label class="form-label">Keterangan</label>
                        <input type="text" name="notes" class="form-control" placeholder="Contoh: Tidak masuk tanpa keterangan">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Status</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    setInterval(function () {
        const now = new Date();
        const el = document.getElementById('jam-sekarang');
        if (el) {
            el.innerText = [now.getHours(), now.getMinutes(), now.getSeconds()]
                .map(n => String(n).padStart(2, '0')).join(':');
        }
    }, 1000);

    const previewCanvas = document.getElementById('admin-qr-preview');
    if (previewCanvas && window.loadQRCode) {
        window.loadQRCode().then((QRCode) => {
            QRCode.toCanvas(previewCanvas, previewCanvas.dataset.token, {
                width: 170,
                margin: 2,
                color: { dark: '#111827', light: '#FFFFFF' }
            });
        });
    }

    let html5QrCode = null;
    let scanBusy = false;
    const modalEl = document.getElementById('qrScannerModal');
    const startBtn = document.getElementById('btn-start-qr-camera');
    const stopBtn = document.getElementById('btn-stop-qr-camera');
    const placeholder = document.getElementById('qr-camera-placeholder');
    const feedback = document.getElementById('qr-camera-feedback');

    function showFeedback(type, msg) {
        feedback.className = 'alert alert-' + type + ' small mb-3';
        feedback.innerText = msg;
    }

    async function stopScanner() {
        if (html5QrCode) {
            try {
                await html5QrCode.stop();
                html5QrCode.clear();
            } catch (e) {}
            html5QrCode = null;
        }
        startBtn?.classList.remove('d-none');
        stopBtn?.classList.add('d-none');
        placeholder?.classList.remove('d-none');
    }

    async function submitScannedToken(decodedText) {
        if (scanBusy) return;
        scanBusy = true;
        document.getElementById('qr_token_input').value = decodedText.trim();
        await stopScanner();
        showFeedback('info', 'QR terdeteksi! Memproses absensi...');

        try {
            const resp = await fetch("{{ route('hr.attendance.scan-qr') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ qr_token: decodedText.trim() })
            });
            const data = await resp.json();
            if (resp.ok && data.ok) {
                showFeedback('success', data.message);
                setTimeout(() => window.location.reload(), 900);
            } else {
                showFeedback('danger', data.message || 'QR tidak valid.');
                scanBusy = false;
            }
        } catch (err) {
            document.getElementById('qr-manual-form').submit();
        }
    }

    startBtn?.addEventListener('click', async function () {
        if (!window.loadHtml5Qrcode) {
            showFeedback('warning', 'Kamera scanner belum siap. Gunakan kolom input kode QR di bawah.');
            return;
        }
        feedback.classList.add('d-none');
        placeholder.classList.add('d-none');

        try {
            const Html5Qrcode = await window.loadHtml5Qrcode();
            html5QrCode = new Html5Qrcode('qr-reader');
            await html5QrCode.start(
                { facingMode: 'environment' },
                { fps: 10, qrbox: { width: 210, height: 210 } },
                (decodedText) => submitScannedToken(decodedText),
                () => {}
            );
            startBtn.classList.add('d-none');
            stopBtn.classList.remove('d-none');
        } catch (err) {
            placeholder.classList.remove('d-none');
            showFeedback('warning', 'Kamera tidak dapat diakses dari browser ini. Silakan tempel / ketik kode QR pada kolom di bawah.');
            html5QrCode = null;
        }
    });

    stopBtn?.addEventListener('click', stopScanner);
    modalEl?.addEventListener('hidden.bs.modal', stopScanner);
});
</script>
@endpush
