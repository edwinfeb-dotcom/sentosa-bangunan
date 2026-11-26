@extends('layouts.app')

@section('title', 'Detail Pelanggan')

@section('content')
<div class="page-header">
    <div class="d-flex align-items-center gap-3">
        <a href="{{ route('master.customers.index') }}" class="btn btn-light border btn-sm">
            <i class="ph ph-arrow-left"></i>
        </a>
        <div>
            <div class="d-flex align-items-center gap-2">
                <h2 class="page-title mb-0">{{ $customer->name }}</h2>
                <span class="badge bg-dark text-white text-uppercase">{{ $customer->type }}</span>
            </div>
            <p class="page-subtitle">
                {{ $customer->phone ?? 'Tanpa No. WA' }}
                @if($customer->address) • {{ $customer->address }} @endif
            </p>
        </div>
    </div>
    <div class="d-flex flex-wrap gap-2">
        @if($customer->phone)
            @php
                $waNum = preg_replace('/[^0-9]/', '', $customer->phone);
                if (str_starts_with($waNum, '0')) {
                    $waNum = '62' . substr($waNum, 1);
                }
                $waText = rawurlencode("Halo {$customer->name}, salam dari Sentosa Bangunan. Poin Bintang Gold Anda saat ini {$customer->points}.");
            @endphp
            <a href="https://wa.me/{{ $waNum }}?text={{ $waText }}" target="_blank" class="btn btn-outline-success">
                <i class="ph ph-whatsapp-logo"></i> Follow-Up WA
            </a>
        @endif
        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#topUpDepositModal">
            <i class="ph ph-wallet"></i> Top Up Deposit
        </button>
        <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#debtModal">
            <i class="ph ph-hand-coins"></i> Catat / Lunasi Bon
        </button>
        <a href="{{ route('master.customers.edit', $customer->id) }}" class="btn btn-light border">
            <i class="ph ph-pencil-simple text-accent"></i> Edit Pelanggan
        </a>
    </div>
</div>

<!-- Ringkasan Pelanggan -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-label">Bintang Gold</div>
            <div class="stat-value text-warning">
                <i class="ph-fill ph-star"></i> {{ number_format($customer->points, 0, ',', '.') }}
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-label">Saldo Deposit</div>
            <div class="stat-value text-success">Rp {{ number_format($customer->deposit_balance, 0, ',', '.') }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-label">Sisa Bon / Piutang</div>
            <div class="stat-value {{ (float) $customer->debt_balance > 0 ? 'text-danger' : 'text-dark' }}">
                Rp {{ number_format($customer->debt_balance ?? 0, 0, ',', '.') }}
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-label">Total Belanja POS &amp; Riwayat</div>
            <div class="stat-value">Rp {{ number_format($totalSpentPos + (float) ($customer->total_spent_history ?? 0), 0, ',', '.') }}</div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Mutasi Deposit -->
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header"><i class="ph ph-wallet text-accent me-2"></i>Riwayat Mutasi Deposit</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Keterangan</th>
                                <th class="text-end">Nominal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($depositLedgers as $dl)
                                <tr>
                                    <td class="small text-muted">{{ $dl->created_at->format('d/m/Y H:i') }}</td>
                                    <td>
                                        <div class="small fw-medium text-dark">{{ $dl->description ?? '-' }}</div>
                                        <small class="text-muted">Oleh: {{ $dl->user->name ?? '-' }}</small>
                                    </td>
                                    <td class="text-end fw-bold {{ $dl->type === 'in' ? 'text-success' : 'text-danger' }}">
                                        {{ $dl->type === 'in' ? '+' : '-' }} Rp {{ number_format($dl->amount, 0, ',', '.') }}
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-center py-4 text-muted">Belum ada mutasi deposit.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($depositLedgers->hasPages())
                <div class="card-footer bg-white">{{ $depositLedgers->links() }}</div>
            @endif
        </div>

        <div class="card mt-4">
            <div class="card-header"><i class="ph ph-hand-coins text-danger me-2"></i>Riwayat Mutasi Bon / Piutang</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Keterangan</th>
                                <th class="text-end">Nominal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($debtLedgers as $bl)
                                <tr>
                                    <td class="small text-muted">{{ $bl->created_at->format('d/m/Y H:i') }}</td>
                                    <td>
                                        <div class="small fw-medium text-dark">{{ $bl->description ?? '-' }}</div>
                                        <small class="text-muted">Oleh: {{ $bl->user->name ?? '-' }}</small>
                                    </td>
                                    <td class="text-end fw-bold {{ $bl->type === 'in' ? 'text-danger' : 'text-success' }}">
                                        {{ $bl->type === 'in' ? '+ Bon' : 'Bayar' }} Rp {{ number_format($bl->amount, 0, ',', '.') }}
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-center py-4 text-muted">Belum ada catatan bon pelanggan.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($debtLedgers->hasPages())
                <div class="card-footer bg-white">{{ $debtLedgers->links() }}</div>
            @endif
        </div>
    </div>

    <!-- Riwayat Belanja POS -->
    <div class="col-lg-7">
        <div class="card mb-4">
            <div class="card-header"><i class="ph ph-receipt text-accent me-2"></i>Riwayat Transaksi Kasir (POS)</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>No. Invoice</th>
                                <th>Tanggal</th>
                                <th>Metode</th>
                                <th class="text-end">Total</th>
                                <th class="text-end">Struk</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($sales as $sale)
                                <tr>
                                    <td class="fw-bold font-monospace">{{ $sale->invoice_number }}</td>
                                    <td class="small text-muted">{{ $sale->created_at->format('d/m/Y H:i') }}</td>
                                    <td><span class="badge bg-light text-dark border text-uppercase">{{ $sale->payments->first()?->method ?? '-' }}</span></td>
                                    <td class="text-end fw-bold">Rp {{ number_format($sale->total_amount, 0, ',', '.') }}</td>
                                    <td class="text-end">
                                        <a href="{{ route('pos.receipt', $sale->id) }}" class="btn btn-sm btn-light border">
                                            <i class="ph ph-printer"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center py-4 text-muted">Belum ada riwayat transaksi POS.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($sales->hasPages())
                <div class="card-footer bg-white">{{ $sales->links() }}</div>
            @endif
        </div>

        @if($onlineOrders->isNotEmpty())
        <div class="card">
            <div class="card-header"><i class="ph ph-globe text-accent me-2"></i>Riwayat Pesanan Online Terbaru</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Kode Order</th>
                                <th>Tanggal</th>
                                <th>Status</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($onlineOrders as $ord)
                                <tr>
                                    <td class="fw-bold font-monospace">{{ $ord->order_code }}</td>
                                    <td class="small text-muted">{{ $ord->created_at->format('d/m/Y H:i') }}</td>
                                    <td><span class="badge {{ $ord->status_badge_class }}">{{ $ord->status_label }}</span></td>
                                    <td class="text-end fw-bold">Rp {{ number_format($ord->total_amount, 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>

<!-- Modal Top Up Deposit -->
<div class="modal fade" id="topUpDepositModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Top Up Deposit: {{ $customer->name }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('master.customers.deposit', $customer->id) }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label">Nominal Titipan (Rp)</label>
                        <input type="number" name="amount" class="form-control form-control-lg tabular-nums" required min="1">
                    </div>
                    <div>
                        <label class="form-label">Keterangan</label>
                        <input type="text" name="description" class="form-control" placeholder="Contoh: Titip uang belanja proyek">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success">Simpan Deposit</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Catat / Lunasi Bon -->
<div class="modal fade" id="debtModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Kelola Bon Pelanggan: {{ $customer->name }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('master.customers.debt', $customer->id) }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label">Jenis Transaksi</label>
                        <select name="action_type" class="form-select" required>
                            @if((float) ($customer->debt_balance ?? 0) > 0)
                                <option value="out">Pelunasan / Bayar Bon (Masuk Kas)</option>
                            @endif
                            <option value="in">Tambah Bon / Piutang Baru</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nominal (Rp)</label>
                        <input type="number" name="amount" class="form-control form-control-lg tabular-nums" required min="1" value="{{ (int) ($customer->debt_balance ?? 0) > 0 ? (int) $customer->debt_balance : '' }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Dompet Penerima (Saat Pelunasan)</label>
                        <select name="wallet_id" class="form-select">
                            @foreach($wallets as $w)
                                <option value="{{ $w->id }}">{{ $w->name }} ({{ strtoupper($w->type) }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Keterangan</label>
                        <input type="text" name="description" class="form-control" placeholder="Contoh: Cicilan bon bahan bangunan">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Catatan Bon</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
