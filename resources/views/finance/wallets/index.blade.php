@extends('layouts.app')

@section('title', 'Dompet & Kas (Multi-Rekening)')

@section('content')
<div class="page-header">
    <div>
        <h2 class="page-title">Dompet & Kas Multi-Rekening</h2>
        <p class="page-subtitle">Pisahkan saldo Kas Tunai (Laci) dan Rekening Bank secara akurat.</p>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#transferWalletModal">
            <i class="ph ph-arrows-left-right"></i> Transfer Antar Kas
        </button>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addWalletModal">
            <i class="ph ph-plus"></i> Tambah Dompet
        </button>
    </div>
</div>

<!-- Total Liquid Balance -->
<div class="row g-3 mb-4">
    <div class="col-md-5">
        <div class="stat-card" style="border-left: 4px solid #1E3A8A;">
            <div class="stat-label">Total Seluruh Saldo Kas & Bank</div>
            <div class="stat-value fs-4 text-primary">Rp {{ number_format($totalBalance, 0, ',', '.') }}</div>
        </div>
    </div>
</div>

<div class="row g-3">
    @forelse($wallets as $wallet)
        <div class="col-md-6 col-lg-4">
            <div class="card h-100">
                <div class="card-body p-4 d-flex flex-column justify-content-between">
                    <div>
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div class="d-flex align-items-center gap-2">
                                <div class="stat-icon {{ $wallet->type === 'cash' ? 'bg-success bg-opacity-10 text-success' : 'bg-primary bg-opacity-10 text-primary' }}">
                                    <i class="ph {{ $wallet->type === 'cash' ? 'ph-money' : 'ph-bank' }}"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold mb-0">{{ $wallet->name }}</h6>
                                    <small class="text-muted text-uppercase">{{ $wallet->type }} {{ $wallet->account_number ? '• ' . $wallet->account_number : '' }}</small>
                                </div>
                            </div>
                            <span class="badge {{ $wallet->is_active ? 'bg-success bg-opacity-10 text-success' : 'bg-danger bg-opacity-10 text-danger' }}">
                                {{ $wallet->is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </div>
                        <div class="small text-muted">Saldo Saat Ini</div>
                        <div class="fs-4 fw-bold text-dark mb-3">Rp {{ number_format($wallet->current_balance, 0, ',', '.') }}</div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center pt-3 border-top small">
                        <span class="text-muted">Saldo Awal: Rp {{ number_format($wallet->initial_balance, 0, ',', '.') }}</span>
                        <button type="button" class="btn btn-sm btn-light border" data-bs-toggle="modal" data-bs-target="#editWalletModal{{ $wallet->id }}">
                            <i class="ph ph-pencil-simple"></i> Edit
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12 text-center py-5 text-muted">Belum ada dompet kas.</div>
    @endforelse
</div>

<!-- Modal Tambah Dompet -->
<div class="modal fade" id="addWalletModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Tambah Dompet / Rekening Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('finance.wallets.store') }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label">Nama Dompet / Bank</label>
                        <input type="text" name="name" class="form-control" placeholder="Contoh: Bank Mandiri / QRIS" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tipe</label>
                        <select name="type" class="form-select" required>
                            <option value="cash">Kas Tunai (Laci)</option>
                            <option value="bank">Rekening Bank</option>
                            <option value="ewallet">E-Wallet / QRIS</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nomor Rekening (Opsional)</label>
                        <input type="text" name="account_number" class="form-control font-monospace" placeholder="Contoh: 123-456-7890">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Atas Nama Pemilik Rekening (Opsional)</label>
                        <input type="text" name="account_holder" class="form-control" placeholder="Contoh: Sentosa Bangunan">
                    </div>
                    <div>
                        <label class="form-label">Saldo Awal (Rp)</label>
                        <input type="number" name="initial_balance" class="form-control" value="0" min="0" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Transfer Antar Dompet -->
<div class="modal fade" id="transferWalletModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Transfer Saldo Antar Kas / Bank</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('finance.wallets.transfer') }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label">Dari Dompet (Sumber)</label>
                        <select name="from_wallet_id" class="form-select" required>
                            @foreach($wallets as $w)
                                <option value="{{ $w->id }}">{{ $w->name }} (Rp {{ number_format($w->current_balance, 0, ',', '.') }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Ke Dompet (Tujuan)</label>
                        <select name="to_wallet_id" class="form-select" required>
                            @foreach($wallets as $w)
                                <option value="{{ $w->id }}">{{ $w->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label">Nominal (Rp)</label>
                            <input type="number" name="amount" class="form-control" min="1" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Tanggal</label>
                            <input type="date" name="date" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>
                    </div>
                    <div>
                        <label class="form-label">Catatan (Opsional)</label>
                        <input type="text" name="notes" class="form-control" placeholder="Contoh: Setor tunai ke bank">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Proses Transfer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Modals -->
@foreach($wallets as $wallet)
<div class="modal fade" id="editWalletModal{{ $wallet->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Edit Dompet: {{ $wallet->name }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('finance.wallets.update', $wallet->id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label">Nama Dompet</label>
                        <input type="text" name="name" class="form-control" value="{{ $wallet->name }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tipe</label>
                        <select name="type" class="form-select" required>
                            <option value="cash" {{ $wallet->type == 'cash' ? 'selected' : '' }}>Kas Tunai</option>
                            <option value="bank" {{ $wallet->type == 'bank' ? 'selected' : '' }}>Rekening Bank</option>
                            <option value="ewallet" {{ $wallet->type == 'ewallet' ? 'selected' : '' }}>E-Wallet</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nomor Rekening</label>
                        <input type="text" name="account_number" class="form-control font-monospace" value="{{ $wallet->account_number }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Atas Nama Pemilik Rekening</label>
                        <input type="text" name="account_holder" class="form-control" value="{{ $wallet->account_holder }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Saldo Awal (Rp)</label>
                        <input type="number" name="initial_balance" class="form-control" value="{{ $wallet->initial_balance }}" min="0" required>
                    </div>
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="walletActive{{ $wallet->id }}" {{ $wallet->is_active ? 'checked' : '' }}>
                        <label class="form-check-label" for="walletActive{{ $wallet->id }}">Dompet Aktif</label>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="show_on_catalog" value="1" id="walletCatalog{{ $wallet->id }}" {{ $wallet->show_on_catalog ? 'checked' : '' }}>
                        <label class="form-check-label" for="walletCatalog{{ $wallet->id }}">Tampilkan ke Pembeli (Katalog Transfer)</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach
@endsection
