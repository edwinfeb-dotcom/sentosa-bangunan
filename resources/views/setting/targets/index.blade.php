@extends('layouts.app')

@section('title', 'Strategi & Target Bisnis')

@section('content')
@php
    $bulanList = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
    $mName = $bulanList[$strategyMetrics['month']] ?? $strategyMetrics['month'];
@endphp

<div class="page-header">
    <div>
        <h2 class="page-title">Strategi &amp; Target Bisnis</h2>
        <p class="page-subtitle">Rancang target omzet, target profit, batas biaya operasional, dan pantau barang unggulan vs mengendap ({{ $mName }} {{ $strategyMetrics['year'] }}).</p>
    </div>
    <form action="{{ route('setting.targets.index') }}" method="GET" class="d-flex align-items-center gap-2">
        <select name="month" class="form-select form-select-sm" style="width: 130px;">
            @foreach($bulanList as $num => $nama)
                <option value="{{ $num }}" {{ $strategyMetrics['month'] === $num ? 'selected' : '' }}>{{ $nama }}</option>
            @endforeach
        </select>
        <input type="number" name="year" class="form-control form-control-sm" value="{{ $strategyMetrics['year'] }}" style="width: 85px;">
        <button type="submit" class="btn btn-sm btn-dark">Lihat Periode</button>
    </form>
</div>

<!-- 4 Kartu Ringkasan Strategi Bulan Aktif -->
<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-label">Target Omzet ({{ $mName }})</div>
            <div class="stat-value">Rp {{ number_format($strategyMetrics['actual_omzet'], 0, ',', '.') }}</div>
            <div class="small text-muted mt-1 tabular-nums">
                Target: <strong>Rp {{ number_format($strategyMetrics['target_amount'], 0, ',', '.') }}</strong> ({{ $strategyMetrics['omzet_pct'] }}%)
            </div>
            <div class="progress mt-2" style="height: 5px;">
                <div class="progress-bar {{ $strategyMetrics['omzet_pct'] >= 100 ? 'bg-success' : 'bg-primary' }}" style="width: {{ $strategyMetrics['omzet_pct'] }}%;"></div>
            </div>
        </div>
    </div>

    <div class="col-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-label">Target Laba Kotor (Profit)</div>
            <div class="stat-value text-success">Rp {{ number_format($strategyMetrics['actual_profit'], 0, ',', '.') }}</div>
            <div class="small text-muted mt-1 tabular-nums">
                Target: <strong>{{ $strategyMetrics['target_profit'] > 0 ? 'Rp ' . number_format($strategyMetrics['target_profit'], 0, ',', '.') : 'Belum diatur' }}</strong>
            </div>
            @if($strategyMetrics['target_profit'] > 0)
            <div class="progress mt-2" style="height: 5px;">
                <div class="progress-bar bg-success" style="width: {{ $strategyMetrics['profit_pct'] }}%;"></div>
            </div>
            @endif
        </div>
    </div>

    <div class="col-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-label">Plafon Biaya Operasional</div>
            <div class="stat-value {{ $strategyMetrics['max_expense'] > 0 && $strategyMetrics['actual_expense'] > $strategyMetrics['max_expense'] ? 'text-danger' : 'text-dark' }}">
                Rp {{ number_format($strategyMetrics['actual_expense'], 0, ',', '.') }}
            </div>
            <div class="small text-muted mt-1 tabular-nums">
                Batas Maks: <strong>{{ $strategyMetrics['max_expense'] > 0 ? 'Rp ' . number_format($strategyMetrics['max_expense'], 0, ',', '.') : 'Belum diatur' }}</strong>
            </div>
            @if($strategyMetrics['max_expense'] > 0)
            <div class="progress mt-2" style="height: 5px;">
                <div class="progress-bar {{ $strategyMetrics['actual_expense'] > $strategyMetrics['max_expense'] ? 'bg-danger' : 'bg-warning' }}" style="width: {{ $strategyMetrics['expense_pct'] }}%;"></div>
            </div>
            @endif
        </div>
    </div>

    <div class="col-6 col-xl-3">
        <div class="stat-card" style="background-color: var(--sb-sidebar-bg);">
            <div class="stat-label">Simulasi Pace Harian</div>
            <div class="stat-value text-accent">
                Rp {{ number_format($strategyMetrics['daily_catchup_needed'], 0, ',', '.') }}<span class="fs-6 fw-normal text-muted">/hr</span>
            </div>
            <div class="small text-muted mt-1 tabular-nums">
                Rata-rata ideal: Rp {{ number_format($strategyMetrics['daily_target_base'], 0, ',', '.') }}/hr ({{ $strategyMetrics['working_days'] }} hari kerja)
            </div>
        </div>
    </div>
</div>

@if($currentTarget && ($currentTarget->focusCategory || $currentTarget->strategy_plan))
<div class="alert border mb-4" style="background-color: var(--sb-accent-soft); border-color: var(--sb-accent-border) !important;">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
        <div>
            <div class="fw-bold text-dark small mb-1">
                <i class="ph ph-compass text-accent me-1"></i>
                Fokus Strategi {{ $mName }} {{ $strategyMetrics['year'] }}
                @if($currentTarget->focusCategory)
                    — <span class="badge bg-dark text-white ms-1">Kategori Fokus: {{ $currentTarget->focusCategory->name }}</span>
                @endif
            </div>
            @if($currentTarget->strategy_plan)
                <div class="small text-secondary" style="white-space: pre-line;">{{ $currentTarget->strategy_plan }}</div>
            @endif
        </div>
    </div>
</div>
@endif

<div class="row g-4 mb-4">
    <!-- Form Atur Strategi & Target -->
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header"><i class="ph ph-sliders text-accent me-2"></i>Atur Strategi &amp; Target Bulanan</div>
            <div class="card-body p-4">
                <form action="{{ route('setting.targets.store') }}" method="POST">
                    @csrf
                    <div class="row g-2 mb-3">
                        <div class="col-5">
                            <label class="form-label">Bulan</label>
                            <select name="month" class="form-select" required>
                                @foreach($bulanList as $num => $nama)
                                    <option value="{{ $num }}" {{ $strategyMetrics['month'] == $num ? 'selected' : '' }}>{{ $nama }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-4">
                            <label class="form-label">Tahun</label>
                            <input type="number" name="year" class="form-control tabular-nums" value="{{ $strategyMetrics['year'] }}" required>
                        </div>
                        <div class="col-3">
                            <label class="form-label">Hari Kerja</label>
                            <input type="number" name="working_days" class="form-control tabular-nums" value="{{ $currentTarget->working_days ?? 26 }}" min="1" max="31">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Target Omzet Bulanan (Rp)</label>
                        <input type="number" name="target_amount" class="form-control form-control-lg tabular-nums" min="0" value="{{ (float) ($currentTarget->target_amount ?? 0) ?: '' }}" placeholder="50000000" required>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label">Target Profit / Laba (Rp)</label>
                            <input type="number" name="target_profit" class="form-control tabular-nums" min="0" value="{{ (float) ($currentTarget->target_profit ?? 0) }}" placeholder="10000000">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Batas Biaya Ops (Rp)</label>
                            <input type="number" name="max_expense" class="form-control tabular-nums" min="0" value="{{ (float) ($currentTarget->max_expense ?? 0) }}" placeholder="5000000">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Kategori Fokus Bulan Ini (Opsional)</label>
                        <select name="focus_category_id" class="form-select">
                            <option value="">-- Semua Kategori --</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" {{ ($currentTarget->focus_category_id ?? '') == $cat->id ? 'selected' : '' }}>
                                    {{ $cat->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Rencana Strategi &amp; Promo</label>
                        <textarea name="strategy_plan" class="form-control" rows="3" placeholder="Misal: Fokus dorong penjualan cat &amp; keramik, follow-up kontraktor proyek, promo gratis ongkir min. 1 jt...">{{ $currentTarget->strategy_plan ?? '' }}</textarea>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Judul Singkat / Catatan</label>
                        <input type="text" name="notes" class="form-control" value="{{ $currentTarget->notes ?? '' }}" placeholder="Target ekspansi / promo akhir bulan">
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        <i class="ph ph-floppy-disk"></i> Simpan Strategi &amp; Target
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Analisis Produk Strategis + Riwayat -->
    <div class="col-lg-7">
        <div class="row g-3 mb-4">
            <!-- Top 5 Terlaris -->
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header"><i class="ph ph-fire text-accent me-2"></i>Produk Terlaris (30 Hari)</div>
                    <div class="card-body p-0">
                        <table class="table table-sm">
                            <tbody>
                                @forelse($fastMoving as $fm)
                                    <tr>
                                        <td>
                                            <div class="fw-semibold text-dark small">{{ $fm->name }}</div>
                                            <small class="text-muted tabular-nums">Rp {{ number_format($fm->total_omzet, 0, ',', '.') }}</small>
                                        </td>
                                        <td class="text-end">
                                            <span class="badge bg-success bg-opacity-10 text-success tabular-nums">{{ $fm->total_qty }} {{ $fm->unit }}</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td class="text-center py-4 text-muted small">Belum ada penjualan 30 hari terakhir.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Top 5 Barang Mengendap -->
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header"><i class="ph ph-hourglass-medium text-warning me-2"></i>Barang Mengendap (30 Hari)</div>
                    <div class="card-body p-0">
                        <table class="table table-sm">
                            <tbody>
                                @forelse($slowMoving as $sm)
                                    <tr>
                                        <td>
                                            <div class="fw-semibold text-dark small">{{ $sm->name }}</div>
                                            <small class="text-muted tabular-nums">Modal tertahan: Rp {{ number_format($sm->current_stock * (float) $sm->purchase_price, 0, ',', '.') }}</small>
                                        </td>
                                        <td class="text-end">
                                            <span class="badge bg-warning bg-opacity-10 text-warning tabular-nums">{{ $sm->current_stock }} {{ $sm->unit }}</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td class="text-center py-4 text-success small">Semua barang aktif terjual 30 hari ini.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabel Riwayat Target & Pencapaian -->
        <div class="card">
            <div class="card-header">Riwayat Target &amp; Pencapaian Bulanan</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Periode</th>
                                <th>Target Omzet</th>
                                <th>Realisasi</th>
                                <th>Profit / Biaya</th>
                                <th>Pencapaian</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($targets as $t)
                                <tr>
                                    <td>
                                        <div class="fw-bold text-dark">{{ $bulanList[$t->month] ?? $t->month }} {{ $t->year }}</div>
                                        @if($t->focusCategory)
                                            <small class="text-accent">{{ $t->focusCategory->name }}</small>
                                        @endif
                                    </td>
                                    <td>Rp {{ number_format($t->target_amount, 0, ',', '.') }}</td>
                                    <td class="fw-semibold text-dark">Rp {{ number_format($t->actual_amount, 0, ',', '.') }}</td>
                                    <td class="small">
                                        <div class="text-success">+Rp {{ number_format($t->actual_profit, 0, ',', '.') }}</div>
                                        <div class="text-danger">-Rp {{ number_format($t->actual_expense, 0, ',', '.') }}</div>
                                    </td>
                                    <td style="min-width: 130px;">
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
                                <tr><td colspan="6" class="text-center py-5 text-muted">Belum ada target penjualan diatur.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
