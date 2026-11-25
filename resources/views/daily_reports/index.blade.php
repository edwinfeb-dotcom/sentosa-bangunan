@extends('layouts.app')

@section('title', 'Laporan Harian')

@section('content')
<div class="page-header">
    <div>
        <h2 class="page-title">Laporan Harian Operasional</h2>
        <p class="page-subtitle">Laporan 8 langkah dari kasir/admin beserta dokumentasi dan kode verifikasi.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('daily-reports.export') }}" class="btn btn-outline-success">
            <i class="ph ph-file-xls"></i> Export Excel
        </a>
        <a href="{{ route('daily-reports.create') }}" class="btn btn-primary">
            <i class="ph ph-plus"></i> Buat Laporan
        </a>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Petugas</th>
                        <th>Total Kasir</th>
                        <th>Kode Verifikasi</th>
                        <th>Status</th>
                        <th class="text-end">Detail & Verifikasi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reports as $report)
                    @php $data = $report->report_data ?? []; @endphp
                    <tr>
                        <td class="fw-semibold">{{ \Carbon\Carbon::parse($report->date)->format('d/m/Y') }}</td>
                        <td>{{ $report->user->name ?? '-' }}</td>
                        <td class="fw-semibold text-primary">Rp {{ number_format($data['laporan_kasir']['total_kasir'] ?? 0, 0, ',', '.') }}</td>
                        <td>
                            <span class="badge bg-secondary bg-opacity-10 text-dark font-monospace">{{ $report->verification_code }}</span>
                        </td>
                        <td>
                            @if($report->status === 'verified')
                                <span class="badge bg-success bg-opacity-10 text-success">Terverifikasi</span>
                            @else
                                <span class="badge bg-warning bg-opacity-10 text-warning">Menunggu Verifikasi</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="d-inline-flex align-items-center gap-2">
                                <button type="button" class="btn btn-sm btn-light border" data-bs-toggle="modal" data-bs-target="#detailReportModal{{ $report->id }}">
                                    <i class="ph ph-eye"></i> Detail
                                </button>
                                @if($report->status === 'submitted' && in_array(auth()->user()->role, ['admin', 'owner']))
                                    <form action="{{ route('daily-reports.verify', $report->id) }}" method="POST" class="d-inline-flex gap-1">
                                        @csrf
                                        <input type="text" name="code" class="form-control form-control-sm" placeholder="Kode" required style="width: 110px;">
                                        <button type="submit" class="btn btn-sm btn-success">Verifikasi</button>
                                    </form>
                                @elseif($report->status === 'verified')
                                    <small class="text-muted">Oleh: {{ $report->verifier->name ?? '-' }}</small>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            <i class="ph ph-clipboard-text fs-1 d-block mb-2 opacity-50"></i>
                            Belum ada laporan harian.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($reports->hasPages())
        <div class="card-footer bg-white">{{ $reports->links() }}</div>
    @endif
</div>

<!-- Detail Modals (di luar tabel agar HTML valid) -->
@foreach($reports as $report)
@php $data = $report->report_data ?? []; @endphp
<div class="modal fade" id="detailReportModal{{ $report->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Detail Laporan — {{ \Carbon\Carbon::parse($report->date)->format('d/m/Y') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3 mb-3">
                    <div class="col-md-4"><div class="p-3 bg-light rounded"><strong>Uang Kecil:</strong><br>Rp {{ number_format($data['laporan_kasir']['uang_kecil'] ?? 0, 0, ',', '.') }}</div></div>
                    <div class="col-md-4"><div class="p-3 bg-light rounded"><strong>Uang Besar:</strong><br>Rp {{ number_format($data['laporan_kasir']['uang_besar'] ?? 0, 0, ',', '.') }}</div></div>
                    <div class="col-md-4"><div class="p-3 bg-light rounded"><strong>Transfer:</strong><br>Rp {{ number_format($data['laporan_kasir']['transfer'] ?? 0, 0, ',', '.') }}</div></div>
                </div>
                <div class="mb-3"><strong>1. Piket:</strong><div class="text-muted small">{{ $data['piket'] ?? '-' }}</div></div>
                <div class="mb-3"><strong>3. Barang & Supplier:</strong><div class="text-muted small">{{ $data['barang_datang'] ?? '-' }}</div></div>
                <div class="mb-3"><strong>4. Pemeriksaan Aset:</strong><div class="text-muted small">{{ $data['cek_aset'] ?? '-' }}</div></div>
                <div class="mb-3"><strong>5. Absensi:</strong><div class="text-muted small">{{ $data['absensi'] ?? '-' }}</div></div>
                <div class="mb-3"><strong>7. Ceklis Sore:</strong><div class="text-muted small">{{ $data['ceklis_sore'] ?? '-' }}</div></div>
                @if(!empty($data['checked_items']))
                    <div class="mb-3">
                        <strong>Item Ceklis Tercentang:</strong>
                        <ul class="small text-success mb-0 mt-1">
                            @foreach($data['checked_items'] as $itemLabel)
                                <li>{{ $itemLabel }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                @if(!empty($data['foto']))
                    <div>
                        <strong>6. Dokumentasi Foto:</strong>
                        <div class="row g-2 mt-1">
                            @foreach($data['foto'] as $fotoPath)
                                <div class="col-4">
                                    <a href="{{ asset('storage/' . $fotoPath) }}" target="_blank">
                                        <img src="{{ asset('storage/' . $fotoPath) }}" class="img-fluid rounded border" style="height: 110px; width: 100%; object-fit: cover;" loading="lazy" decoding="async">
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endforeach
@endsection
