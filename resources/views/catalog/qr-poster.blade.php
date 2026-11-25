@extends('layouts.app')

@section('title', 'QR Web Pelanggan (Katalog)')

@push('styles')
<style>
    .qr-web-poster {
        background: #ffffff;
        border: 2px solid #18181b;
        border-radius: 16px;
        padding: 2.25rem;
    }
    @media print {
        body { background: #ffffff !important; }
        .app-main { margin: 0 !important; padding: 0 !important; }
        .qr-poster-col {
            width: 100% !important;
            max-width: 540px !important;
            margin: 1cm auto !important;
        }
        .qr-web-poster {
            border: 3px solid #18181b !important;
            box-shadow: none !important;
            page-break-inside: avoid;
        }
    }
</style>
@endpush

@section('content')
<div class="page-header d-print-none">
    <div>
        <h2 class="page-title">QR Web Pelanggan (Katalog Online)</h2>
        <p class="page-subtitle">Cetak atau bagikan QR Code ini agar pembeli langsung membuka katalog barang tanpa mengetik alamat web.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('catalog.index') }}" target="_blank" class="btn btn-light border">
            <i class="ph ph-storefront text-accent"></i> Buka Web Katalog
        </a>
        <button type="button" id="btn-copy-catalog-url" class="btn btn-outline-dark" data-url="{{ $catalogUrl }}">
            <i class="ph ph-copy"></i> Salin Link Katalog
        </button>
        <button type="button" id="btn-download-catalog-qr" class="btn btn-outline-primary">
            <i class="ph ph-download-simple"></i> Download PNG
        </button>
        <button type="button" onclick="window.print()" class="btn btn-primary">
            <i class="ph ph-printer"></i> Cetak Poster QR
        </button>
    </div>
</div>

<div class="row g-4 align-items-stretch">
    <div class="col-lg-5 qr-poster-col">
        <div class="qr-web-poster text-center h-100 d-flex flex-column align-items-center justify-content-center">
            <div class="d-flex align-items-center gap-2 mb-2">
                <img src="{{ asset('images/logo.png') }}" alt="Logo Sentosa" style="height: 44px; object-fit: contain;">
                <div class="text-start" style="line-height: 1.15;">
                    <div class="fw-bold text-dark fs-5" style="letter-spacing: -0.02em;">SENTOSA BANGUNAN</div>
                    <div class="text-uppercase fw-bold text-accent" style="font-size: 0.68rem; letter-spacing: 0.08em;">Katalog Material &amp; Pesan Online</div>
                </div>
            </div>

            <span class="badge bg-dark text-white px-3 py-2 my-3">
                <i class="ph ph-qr-code me-1"></i> SCAN UNTUK LIHAT HARGA &amp; STOK
            </span>

            <div class="p-3 bg-white border border-2 rounded-3 mb-3">
                <canvas id="catalog-qr-canvas" data-url="{{ $catalogUrl }}"></canvas>
            </div>

            <div class="badge bg-light text-dark border font-monospace px-3 py-2 mb-3 text-break" style="max-width: 100%; font-size: 0.78rem;">
                {{ $catalogUrl }}
            </div>

            <div class="rounded-3 p-3 w-100 text-start small border" style="background-color: var(--sb-surface-muted);">
                <div class="fw-bold text-dark mb-1">Cara Belanja Pelanggan:</div>
                <ol class="mb-0 ps-3 text-secondary">
                    <li>Buka kamera HP / Google Lens dan arahkan ke QR di atas.</li>
                    <li>Lihat harga &amp; stok material toko secara langsung (tanpa perlu buat akun).</li>
                    <li>Tambahkan ke keranjang &amp; isi Nama, No. WA, dan Alamat saat checkout.</li>
                </ol>
            </div>
        </div>
    </div>

    <div class="col-lg-7 d-print-none">
        <div class="card h-100">
            <div class="card-header">
                <i class="ph ph-info text-accent me-2"></i>Panduan Penggunaan QR Web Pelanggan
            </div>
            <div class="card-body p-4 d-flex flex-column justify-content-between">
                <div>
                    <div class="row g-3 mb-4">
                        <div class="col-sm-6">
                            <div class="stat-card" style="background-color: var(--sb-sidebar-bg);">
                                <div class="stat-label">Produk Aktif di Katalog</div>
                                <div class="stat-value">{{ $productCount }} Item</div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="stat-card" style="background-color: var(--sb-sidebar-bg);">
                                <div class="stat-label">Akses Pembeli</div>
                                <div class="stat-value text-success fs-5">Tanpa Login / Akun</div>
                            </div>
                        </div>
                    </div>

                    <h6 class="fw-bold text-dark mb-2">Di mana sebaiknya QR ini dipasang?</h6>
                    <ul class="text-secondary small ps-3 mb-4 d-flex flex-column gap-2">
                        <li><strong>Meja Kasir / Etalase Toko:</strong> Pembeli yang sedang antre atau cek barang bisa langsung scan melihat daftar harga lengkap.</li>
                        <li><strong>Spanduk Depan Toko / Kartu Nama:</strong> Memudahkan tukang, mandor, dan kontraktor memesan ulang dari lokasi proyek.</li>
                        <li><strong>Status WhatsApp / Media Sosial:</strong> Klik <em>Download PNG</em> di atas lalu bagikan gambar QR-nya ke pelanggan.</li>
                    </ul>
                </div>

                <div class="alert border mb-0 small" style="background-color: var(--sb-accent-soft); border-color: var(--sb-accent-border) !important;">
                    <i class="ph ph-lightbulb text-accent me-1"></i>
                    <strong>Tips:</strong> Jika aplikasi nanti sudah memakai domain resmi (misal <code>https://sentosabangunan.com/katalog</code>), QR otomatis mengikuti alamat website yang sedang dibuka.
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', async function () {
    const canvas = document.getElementById('catalog-qr-canvas');
    const url = canvas?.dataset.url;

    if (canvas && url && window.loadQRCode) {
        const QRCode = await window.loadQRCode();
        QRCode.toCanvas(canvas, url, {
            width: 260,
            margin: 2,
            color: { dark: '#18181b', light: '#ffffff' }
        });

        document.getElementById('btn-download-catalog-qr')?.addEventListener('click', function () {
            const link = document.createElement('a');
            link.download = 'QR-Katalog-Pelanggan-Sentosa.png';
            link.href = canvas.toDataURL('image/png');
            link.click();
        });
    }

    document.getElementById('btn-copy-catalog-url')?.addEventListener('click', function () {
        const btn = this;
        const targetUrl = btn.dataset.url;
        const done = () => {
            const orig = btn.innerHTML;
            btn.innerHTML = '<i class="ph ph-check"></i> Link Tersalin!';
            setTimeout(() => { btn.innerHTML = orig; }, 2000);
        };
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(targetUrl).then(done).catch(() => fallbackCopyText(targetUrl, done));
        } else {
            fallbackCopyText(targetUrl, done);
        }
    });

    function fallbackCopyText(text, cb) {
        const ta = document.createElement('textarea');
        ta.value = text;
        ta.style.position = 'fixed';
        ta.style.opacity = '0';
        document.body.appendChild(ta);
        ta.select();
        try {
            document.execCommand('copy');
            cb();
        } catch (e) {}
        document.body.removeChild(ta);
    }
});
</script>
@endpush
