@extends('layouts.app')

@section('title', 'Target Penjualan Bulanan')

@section('content')
@php
    $bulanList = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
@endphp

<div class="page-header">
    <div>
        <h2 class="page-title">Target Penjualan Bulanan</h2>
        <p class="page-subtitle">Tentukan target omzet bulanan yang tampil di progress bar Pusat Kendali.</p>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">Atur Target Baru / Perbarui</div>
            <div class="card-body p-4">
                <form action="{{ route('setting.targets.store') }}" method="POST">
                    @csrf
                    <div class="row g-2 mb-3">
                        <div class="col-7">
                            <label class="form-label">Bulan</label>
                            <select name="month" class="form-select" required>
                                @foreach($bulanList as $num => $nama)
                                    <option value="{{ $num }}" {{ date('n') == $num ? 'selected' : '' }}>{{ $nama }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-5">
                            <label class="form-label">Tahun</label>
                            <input type="number" name="year" class="form-control" value="{{ date('Y') }}" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nominal Target Omzet (Rp)</label>
                        <input type="number" name="target_amount" class="form-control form-control-lg" min="0" placeholder="50000000" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Catatan (Opsional)</label>
                        <input type="text" name="notes" class="form-control" placeholder="Target promosi akhir tahun">
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Simpan Target</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">Daftar Target & Pencapaian</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Periode</th>
                                <th>Target Omzet</th>
                                <th>Realisasi</th>
                                <th>Pencapaian</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($targets as $t)
                                <tr>
                                    <td class="fw-bold">{{ $bulanList[$t->month] ?? $t->month }} {{ $t->year }}</td>
                                    <td>Rp {{ number_format($t->target_amount, 0, ',', '.') }}</td>
                                    <td class="fw-semibold text-primary">Rp {{ number_format($t->actual_amount, 0, ',', '.') }}</td>
                                    <td style="min-width: 150px;">
                                        <div class="d-flex justify-content-between small mb-1">
                                            <span class="fw-bold">{{ $t->percentage }}%</span>
                                        </div>
                                        <div class="progress" style="height: 6px;">
                                            <div class="progress-bar {{ $t->percentage >= 100 ? 'bg-success' : 'bg-primary' }}" style="width: {{ $t->percentage }}%;"></div>
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        <form action="{{ route('setting.targets.destroy', $t->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus target ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-light border"><i class="ph ph-trash text-danger"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center py-5 text-muted">Belum ada target penjualan diatur.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
