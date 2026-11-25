@extends('layouts.app')

@section('title', 'Cetak Poster & Kiosk QR Absensi')

@push('styles')
<style>
    .qr-poster-sheet {
        background: #ffffff;
        border: 2px solid #1C2430;
        border-radius: 16px;
        padding: 2rem;
    }
    @media print {
        body {
            background: #ffffff !important;
        }
        .app-main {
            margin: 0 !important;
            padding: 0 !important;
        }
        .qr-poster-col {
            width: 100% !important;
            max-width: 560px !important;
            margin: 1cm auto !important;
        }
        .qr-poster-sheet {
            border: 3px solid #111827 !important;
            box-shadow: none !important;
            page-break-inside: avoid;
        }
    }
</style>
@endpush

@section('content')
<div class="page-header d-print-none">
    <div>
        <h2 class="page-title">Cetak Poster &amp; Kiosk QR Absensi</h2>
        <p class="page-subtitle">Cetak poster QR sekali untuk ditempel di pintu masuk/dinding toko agar karyawan tinggal scan langsung.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('hr.attendance.index') }}" class="btn btn-light border">
            <i class="ph ph-arrow-left"></i> Kembali ke Absensi
        </a>
        <form action="{{ route('hr.attendance.qr-refresh') }}" method="POST" onsubmit="return confirm('Buat QR baru? Kode QR lama di mode ini akan otomatis dinonaktifkan.')">
            @csrf
            <input type="hidden" name="type" value="{{ $mode }}">
            <button type="submit" class="btn btn-outline-primary">
                <i class="ph ph-arrows-clockwise"></i> {{ $mode === 'permanent' ? 'Reset Poster QR' : 'Buat QR Harian Baru' }}
            </button>
        </form>
        <button type="button" id="btn-download-qr-png" class="btn btn-outline-success">
            <i class="ph ph-download-simple"></i> Download PNG
        </button>
        <button type="button" onclick="window.print()" class="btn btn-primary">
            <i class="ph ph-printer"></i> Cetak Poster QR
        </button>
    </div>
</div>

<!-- Switch Mode QR Poster Permanen vs QR Harian -->
<div class="d-flex flex-wrap gap-2 mb-4 d-print-none">
    <a href="{{ route('hr.attendance.qr-kiosk', ['mode' => 'permanent']) }}"
       class="btn {{ $mode === 'permanent' ? 'btn-dark' : 'btn-light border' }}">
        <i class="ph ph-push-pin me-1"></i> QR Poster Permanen (Untuk Cetak &amp; Tempel)
    </a>
    <a href="{{ route('hr.attendance.qr-kiosk', ['mode' => 'daily']) }}"
       class="btn {{ $mode === 'daily' ? 'btn-dark' : 'btn-light border' }}">
        <i class="ph ph-calendar me-1"></i> QR Harian ({{ \Carbon\Carbon::parse($today, 'Asia/Pontianak')->format('d/m/Y') }})
    </a>
</div>

<div class="row g-4 align-items-stretch">
    <div class="col-lg-5 qr-poster-col">
        <div class="qr-poster-sheet text-center h-100 d-flex flex-column align-items-center justify-content-center shadow-sm">
            <img src="{{ asset('images/logo.png') }}" alt="Logo Sentosa" style="height: 56px; object-fit: contain;" class="mb-2">
            <h4 class="fw-bold text-dark mb-0">SENTOSA BANGUNAN</h4>
            <div class="text-uppercase fw-bold text-primary small mb-3" style="letter-spacing: 0.08em;">
                Scan QR Absensi Karyawan
            </div>

            <span class="badge {{ $mode === 'permanent' ? 'bg-success bg-opacity-10 text-success border border-success' : 'bg-info bg-opacity-10 text-info border border-info' }} px-3 py-2 mb-3">
                {{ $mode === 'permanent' ? 'POSTER QR PERMANEN • BERLAKU SETIAP HARI' : 'QR HARIAN • ' . \Carbon\Carbon::parse($today, 'Asia/Pontianak')->translatedFormat('l, d F Y') }}
            </span>

            <div class="p-3 bg-white border border-2 rounded-3 mb-3">
                <canvas id="kiosk-qr-canvas" data-token="{{ $activeQrSession->token }}"></canvas>
            </div>

            <div class="badge bg-light text-dark border font-monospace fs-6 px-3 py-2 mb-3">
                {{ $activeQrSession->token }}
            </div>

            <div class="bg-light rounded-3 p-3 w-100 text-start small border">
                <div class="fw-bold text-dark mb-1">Cara Absen Karyawan:</div>
                <ol class="mb-0 ps-3 text-muted">
                    <li>Login akun karyawan di HP masing-masing.</li>
                    <li>Buka menu <strong>Absensi &amp; Scan QR</strong> -> tekan <strong>Scan QR Absen</strong>.</li>
                    <li>Arahkan kamera ke QR ini untuk absen masuk &amp; pulang (tidak perlu ke ruangan admin).</li>
                </ol>
            </div>
        </div>
    </div>

    <div class="col-lg-7 d-print-none">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="ph ph-users-three me-2 text-primary"></i>Karyawan yang Sudah Absen Hari Ini</span>
                <span class="badge bg-primary bg-opacity-10 text-primary">{{ $todayCheckins->count() }} Tercatat</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th>Nama</th>
                                <th>Jabatan</th>
                                <th>Metode</th>
                                <th>Masuk</th>
                                <th>Pulang</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($todayCheckins as $item)
                            <tr>
                                <td class="fw-semibold">{{ $item->user->name ?? '-' }}</td>
                                <td class="text-capitalize">{{ $item->user->role ?? '-' }}</td>
                                <td>
                                    @if(str_contains((string) $item->check_in_method, 'qr'))
                                        <span class="badge bg-success bg-opacity-10 text-success"><i class="ph ph-qr-code me-1"></i>QR</span>
                                    @else
                                        <span class="badge bg-light text-dark border">{{ strtoupper($item->check_in_method ?? 'MANUAL') }}</span>
                                    @endif
                                </td>
                                <td>{{ $item->clock_in ?? '-' }}</td>
                                <td>{{ $item->clock_out ?? 'Belum Pulang' }}</td>
                                <td><span class="badge bg-primary bg-opacity-10 text-primary text-uppercase">{{ $item->status }}</span></td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">Belum ada karyawan yang scan QR atau absen hari ini.</td>
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

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', async function () {
    const canvas = document.getElementById('kiosk-qr-canvas');
    if (canvas && window.loadQRCode) {
        const QRCode = await window.loadQRCode();
        QRCode.toCanvas(canvas, canvas.dataset.token, {
            width: 280,
            margin: 2,
            color: { dark: '#111827', light: '#FFFFFF' }
        });

        const dlBtn = document.getElementById('btn-download-qr-png');
        dlBtn?.addEventListener('click', function () {
            const link = document.createElement('a');
            link.download = 'QR-Absensi-Sentosa-{{ $mode }}.png';
            link.href = canvas.toDataURL('image/png');
            link.click();
        });
    }
});
</script>
@endpush
