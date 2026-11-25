@extends('layouts.app')

@section('title', 'Register Aset & Depresiasi')

@section('content')
<div class="page-header">
    <div>
        <h2 class="page-title">Register Aset & Penyusutan</h2>
        <p class="page-subtitle">Catat aset tetap toko (gedung, kendaraan, komputer) dan hitung nilai penyusutan garis lurus.</p>
    </div>
    <a href="{{ route('fleet.assets.create') }}" class="btn btn-primary">
        <i class="ph ph-plus"></i> Tambah Aset
    </a>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-label">Total Harga Perolehan</div>
            <div class="stat-value">Rp {{ number_format($summary['total_acquisition'], 0, ',', '.') }}</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-label">Akumulasi Penyusutan</div>
            <div class="stat-value text-danger">Rp {{ number_format($summary['total_accumulated_depreciation'], 0, ',', '.') }}</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-label">Total Nilai Buku Saat Ini</div>
            <div class="stat-value text-primary">Rp {{ number_format($summary['total_book_value'], 0, ',', '.') }}</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-label">Beban Penyusutan / Bulan</div>
            <div class="stat-value text-warning">Rp {{ number_format($summary['monthly_depreciation'], 0, ',', '.') }}</div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Kode & Nama Aset</th>
                        <th>Kategori</th>
                        <th>Tgl Beli</th>
                        <th>Harga Perolehan</th>
                        <th>Penyusutan / Bln</th>
                        <th>Nilai Buku</th>
                        <th>Kondisi</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($assets as $asset)
                        <tr>
                            <td>
                                <div class="fw-bold text-dark">{{ $asset->name }}</div>
                                <small class="text-muted font-monospace">{{ $asset->code }} • Umur {{ $asset->useful_life_years }} thn</small>
                            </td>
                            <td><span class="badge bg-light text-dark border">{{ $asset->category }}</span></td>
                            <td>{{ $asset->purchase_date->format('d/m/Y') }}</td>
                            <td class="fw-semibold">Rp {{ number_format($asset->purchase_price, 0, ',', '.') }}</td>
                            <td class="text-danger">Rp {{ number_format($asset->monthly_depreciation, 0, ',', '.') }}</td>
                            <td class="fw-bold text-primary">Rp {{ number_format($asset->book_value, 0, ',', '.') }}</td>
                            <td>
                                @if($asset->condition === 'baik')
                                    <span class="badge bg-success bg-opacity-10 text-success">Baik</span>
                                @elseif($asset->condition === 'perlu_perbaikan')
                                    <span class="badge bg-warning bg-opacity-10 text-warning">Perlu Perbaikan</span>
                                @else
                                    <span class="badge bg-danger bg-opacity-10 text-danger">Rusak</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('fleet.assets.edit', $asset->id) }}" class="btn btn-sm btn-light border"><i class="ph ph-pencil-simple text-primary"></i></a>
                                <form action="{{ route('fleet.assets.destroy', $asset->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus aset ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-light border"><i class="ph ph-trash text-danger"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="ph ph-buildings fs-1 d-block mb-2 opacity-50"></i>
                                Belum ada aset terdaftar.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($assets->hasPages())
        <div class="card-footer bg-white">{{ $assets->links() }}</div>
    @endif
</div>
@endsection
