@extends('layouts.app')

@section('title', 'Master Pelanggan')

@section('content')
<div class="page-header">
    <div>
        <h2 class="page-title">Master Pelanggan</h2>
        <p class="page-subtitle">Kelola data pelanggan, Bintang Gold loyalitas, dan saldo deposit.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <button type="button" class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#importCustomersModal">
            <i class="ph ph-upload-simple"></i> Import Excel (Bon Pelanggan)
        </button>
        <a href="{{ route('master.customers.create') }}" class="btn btn-primary">
            <i class="ph ph-plus"></i> Tambah Pelanggan
        </a>
    </div>
</div>

<!-- Ringkasan Deposit & Bon Beredar -->
<div class="row g-3 mb-4">
    <div class="col-6">
        <div class="stat-card">
            <div class="stat-label">Total Deposit Pelanggan</div>
            <div class="stat-value text-success">Rp {{ number_format($totalCustomersDeposit ?? 0, 0, ',', '.') }}</div>
        </div>
    </div>
    <div class="col-6">
        <div class="stat-card">
            <div class="stat-label">Total Sisa Bon (Piutang Beredar)</div>
            <div class="stat-value {{ ($totalCustomersDebt ?? 0) > 0 ? 'text-danger' : 'text-dark' }}">Rp {{ number_format($totalCustomersDebt ?? 0, 0, ',', '.') }}</div>
        </div>
    </div>
</div>

<!-- Search & Filter -->
<div class="card mb-4">
    <div class="card-body p-3">
        <form action="{{ route('master.customers.index') }}" method="GET" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Cari Nama atau No. Telepon</label>
                <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Ketik nama atau nomor HP...">
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label">Tipe Pelanggan</label>
                <select name="type" class="form-select">
                    <option value="">Semua Tipe</option>
                    <option value="reguler" {{ request('type') == 'reguler' ? 'selected' : '' }}>Reguler</option>
                    <option value="toko" {{ request('type') == 'toko' ? 'selected' : '' }}>Toko</option>
                    <option value="kontraktor" {{ request('type') == 'kontraktor' ? 'selected' : '' }}>Kontraktor</option>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" name="has_debt" value="1" id="hasDebtFilter" {{ request()->boolean('has_debt') ? 'checked' : '' }}>
                    <label class="form-check-label small fw-semibold" for="hasDebtFilter">Punya Sisa Bon</label>
                </div>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary w-100">Filter</button>
                <a href="{{ route('master.customers.index') }}" class="btn btn-light border">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Nama Pelanggan</th>
                        <th>Tipe</th>
                        <th>Bintang Gold</th>
                        <th>Deposit &amp; Sisa Bon</th>
                        <th>Status</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($customers as $customer)
                    <tr>
                        <td>
                            <div class="fw-semibold text-dark">{{ $customer->name }}</div>
                            <small class="text-muted">{{ $customer->phone ?? '-' }}</small>
                        </td>
                        <td>
                            @if($customer->type == 'kontraktor')
                                <span class="badge bg-primary bg-opacity-10 text-primary">Kontraktor</span>
                            @elseif($customer->type == 'toko')
                                <span class="badge bg-info bg-opacity-10 text-info">Toko</span>
                            @else
                                <span class="badge bg-secondary bg-opacity-10 text-secondary">Reguler</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-warning bg-opacity-25 text-dark">
                                <i class="ph-fill ph-star text-warning"></i> {{ $customer->points }}
                            </span>
                        </td>
                        <td>
                            <div class="fw-semibold text-success tabular-nums">Deposit: Rp {{ number_format($customer->deposit_balance, 0, ',', '.') }}</div>
                            @if((float) $customer->debt_balance > 0)
                                <small class="text-danger fw-semibold tabular-nums">Bon: Rp {{ number_format($customer->debt_balance, 0, ',', '.') }}</small>
                            @endif
                        </td>
                        <td>
                            @if($customer->is_active)
                                <span class="badge bg-success bg-opacity-10 text-success">Aktif</span>
                            @else
                                <span class="badge bg-danger bg-opacity-10 text-danger">Nonaktif</span>
                            @endif
                        </td>
                        <td class="text-end">
                            @if($customer->phone)
                                @php
                                    $waNum = preg_replace('/[^0-9]/', '', $customer->phone);
                                    if (str_starts_with($waNum, '0')) {
                                        $waNum = '62' . substr($waNum, 1);
                                    }
                                    $waText = rawurlencode("Halo {$customer->name}, salam dari Sentosa Bangunan. Saat ini Anda memiliki {$customer->points} Bintang Gold.");
                                @endphp
                                <a href="https://wa.me/{{ $waNum }}?text={{ $waText }}" target="_blank" class="btn btn-sm btn-light border text-success" title="Follow-Up WhatsApp">
                                    <i class="ph ph-whatsapp-logo"></i>
                                </a>
                            @endif
                            <a href="{{ route('master.customers.show', $customer->id) }}" class="btn btn-sm btn-light border" title="Detail &amp; Riwayat">
                                <i class="ph ph-eye text-dark"></i>
                            </a>
                            <button type="button" class="btn btn-sm btn-light border text-success" data-bs-toggle="modal" data-bs-target="#depositModal{{ $customer->id }}" title="Top Up Deposit">
                                <i class="ph ph-wallet"></i>
                            </button>
                            <a href="{{ route('master.customers.edit', $customer->id) }}" class="btn btn-sm btn-light border" title="Edit">
                                <i class="ph ph-pencil-simple text-primary"></i>
                            </a>
                            <form action="{{ route('master.customers.destroy', $customer->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Yakin ingin menghapus pelanggan ini?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-light border" title="Hapus">
                                    <i class="ph ph-trash text-danger"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            <i class="ph ph-users fs-1 d-block mb-2 opacity-50"></i>
                            Belum ada data pelanggan.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($customers->hasPages())
        <div class="card-footer bg-white">{{ $customers->links() }}</div>
    @endif
</div>

<!-- Modals rendered cleanly outside table -->
@foreach($customers as $customer)
<div class="modal fade" id="depositModal{{ $customer->id }}" tabindex="-1" aria-hidden="true">
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
                        <input type="number" name="amount" class="form-control form-control-lg" required min="1">
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
@endforeach

<!-- Modal Import Pelanggan Excel -->
<div class="modal fade" id="importCustomersModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Import Pelanggan, Deposit &amp; Bon (Excel)</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('master.customers.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body p-4">
                    <div class="alert alert-info border-0 small mb-3">
                        <div class="fw-bold mb-1">Mendukung File <code>BON PELANGGAN (.xlsx / .xlsm)</code>:</div>
                        Sistem otomatis membaca sheet <code>master</code> dengan kolom <code>Nama Pelanggan</code>, <code>ALAMAT</code>, <code>NOMOR HP</code>, <code>Total Belanja</code>, <code>deposit</code>, dan <code>SISA BON</code>.
                    </div>
                    <label class="form-label fw-semibold">Pilih File Excel (.xlsx, .xlsm, .xls, .csv)</label>
                    <input type="file" name="file" class="form-control" accept=".xlsx,.xlsm,.xls,.csv" required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success"><i class="ph ph-upload-simple"></i> Upload &amp; Import</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
