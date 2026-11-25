@extends('layouts.app')

@section('title', 'Realisasi Material Proyek')

@section('content')
<div class="page-header">
    <div class="d-flex align-items-center gap-3">
        <a href="{{ route('project.projects.index') }}" class="btn btn-light border btn-sm d-print-none"><i class="ph ph-arrow-left"></i></a>
        <div>
            <div class="d-flex align-items-center gap-2">
                <h2 class="page-title mb-0">{{ $project->name }}</h2>
                @if(!$project->customer)
                    <span class="badge bg-dark text-white">Proyek Internal Owner</span>
                @endif
            </div>
            <p class="page-subtitle">
                {{ $project->customer ? 'Kontraktor / Pemilik: ' . $project->customer->name : 'Proyek Pembangunan Internal Owner' }} • Realisasi pengambilan &amp; retur material.
            </p>
        </div>
    </div>
    <button type="button" onclick="window.print()" class="btn btn-outline-dark d-print-none">
        <i class="ph ph-printer"></i> Cetak Rekap Material
    </button>
</div>

<!-- Budget Progress -->
@php
    $used = $project->total_material_cost;
    $remaining = max(0, (float) $project->budget - $used);
    $pct = $project->budget_usage_percent;
@endphp
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-label">Pagu Anggaran</div>
            <div class="stat-value">Rp {{ number_format($project->budget, 0, ',', '.') }}</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-label">Realisasi Material Terpakai</div>
            <div class="stat-value text-accent">Rp {{ number_format($used, 0, ',', '.') }}</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-label">Sisa Anggaran ({{ $pct }}% Terpakai)</div>
            <div class="stat-value text-success">Rp {{ number_format($remaining, 0, ',', '.') }}</div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Form Catat Pengambilan & Retur Material -->
    <div class="col-lg-4 d-print-none">
        <div class="card mb-4">
            <div class="card-header">Catat Pengambilan Material</div>
            <div class="card-body p-4">
                <form action="{{ route('project.projects.materials.store', $project->id) }}" method="POST" x-data="{ price: 0 }">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Pilih Produk Material</label>
                        <select name="product_id" class="form-select" required @change="price = $event.target.options[$event.target.selectedIndex].getAttribute('data-price') || 0">
                            <option value="">-- Pilih Produk --</option>
                            @foreach($products as $product)
                                <option value="{{ $product->id }}" data-price="{{ $product->selling_price }}">
                                    {{ $product->name }} (Stok: {{ $product->current_stock }} {{ $product->unit }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label">Qty</label>
                            <input type="number" name="qty" class="form-control tabular-nums" min="1" value="1" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Harga Satuan (Rp)</label>
                            <input type="number" name="unit_price" class="form-control tabular-nums" x-model="price" min="0" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tanggal Pengambilan</label>
                        <input type="date" name="used_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Catatan (Opsional)</label>
                        <input type="text" name="notes" class="form-control" placeholder="Contoh: Tahap cor lantai 1">
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="ph ph-check-circle"></i> Simpan &amp; Potong Stok
                    </button>
                </form>
            </div>
        </div>

        @if($netTakenMaterials->isNotEmpty())
        <div class="card">
            <div class="card-header"><i class="ph ph-arrow-u-up-left text-accent me-2"></i>Retur Sisa Material ke Toko</div>
            <div class="card-body p-4">
                <form action="{{ route('project.projects.materials.return', $project->id) }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Material yang Dikembalikan</label>
                        <select name="product_id" class="form-select" required>
                            <option value="">-- Pilih Material --</option>
                            @foreach($netTakenMaterials as $matItem)
                                <option value="{{ $matItem['product_id'] }}">
                                    {{ $matItem['product_name'] }} (Terpakai: {{ $matItem['net_qty'] }} {{ $matItem['unit'] }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label">Qty Retur</label>
                            <input type="number" name="qty" class="form-control tabular-nums" min="1" value="1" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Tgl Retur</label>
                            <input type="date" name="used_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Catatan Retur</label>
                        <input type="text" name="notes" class="form-control" placeholder="Contoh: Sisa semen utuh">
                    </div>
                    <button type="submit" class="btn btn-outline-success w-100">
                        <i class="ph ph-arrow-u-up-left"></i> Kembalikan ke Stok Toko
                    </button>
                </form>
            </div>
        </div>
        @endif
    </div>

    <!-- Tabel Riwayat Material -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">Riwayat Realisasi &amp; Retur Material</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Material</th>
                                <th class="text-end">Qty</th>
                                <th class="text-end">Harga</th>
                                <th class="text-end">Subtotal</th>
                                <th>Petugas</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($project->materials as $mat)
                                <tr>
                                    <td>{{ $mat->used_date->format('d/m/Y') }}</td>
                                    <td>
                                        <div class="fw-semibold text-dark">
                                            {{ $mat->product->name ?? '-' }}
                                            @if($mat->qty < 0)
                                                <span class="badge bg-success bg-opacity-10 text-success ms-1">Retur</span>
                                            @endif
                                        </div>
                                        @if($mat->notes)<small class="text-muted">{{ $mat->notes }}</small>@endif
                                    </td>
                                    <td class="text-end {{ $mat->qty < 0 ? 'text-success fw-semibold' : '' }}">{{ $mat->qty }} {{ $mat->product->unit ?? '' }}</td>
                                    <td class="text-end">Rp {{ number_format($mat->unit_price, 0, ',', '.') }}</td>
                                    <td class="text-end fw-bold {{ $mat->subtotal < 0 ? 'text-success' : 'text-dark' }}">
                                        {{ $mat->subtotal < 0 ? '-' : '' }}Rp {{ number_format(abs($mat->subtotal), 0, ',', '.') }}
                                    </td>
                                    <td class="text-muted small">{{ $mat->user->name ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">Belum ada realisasi material untuk proyek ini.</td>
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
