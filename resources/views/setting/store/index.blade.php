@extends('layouts.app')

@section('title', 'Profil Toko & Rekening')

@section('content')
<div class="page-header">
    <div>
        <h2 class="page-title">Profil Toko &amp; Rekening Transfer</h2>
        <p class="page-subtitle">Atur nama, alamat resmi toko (tampil di struk &amp; surat jalan), serta kelola nomor rekening transfer pembeli.</p>
    </div>
    <a href="{{ route('finance.wallets.index') }}" class="btn btn-light border">
        <i class="ph ph-bank text-accent"></i> Kelola Semua Dompet &amp; Saldo
    </a>
</div>

<div class="row g-4">
    <!-- Profil Toko -->
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header"><i class="ph ph-storefront text-accent me-2"></i>Identitas Resmi Toko</div>
            <div class="card-body p-4">
                <form action="{{ route('setting.store.update') }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="mb-3">
                        <label class="form-label">Nama Toko</label>
                        <input type="text" name="store_name" class="form-control" value="{{ old('store_name', $setting->store_name) }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Alamat Lengkap Toko</label>
                        <textarea name="store_address" class="form-control" rows="3" required>{{ old('store_address', $setting->store_address) }}</textarea>
                        <div class="form-text small">Otomatis tercetak di Struk Kasir (POS) dan Surat Jalan Armada.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nomor Telepon / WhatsApp Toko</label>
                        <input type="text" name="store_phone" class="form-control" value="{{ old('store_phone', $setting->store_phone) }}" placeholder="Contoh: 0812-xxxx-xxxx">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Catatan Instruksi Transfer Pembeli</label>
                        <input type="text" name="bank_transfer_note" class="form-control" value="{{ old('bank_transfer_note', $setting->bank_transfer_note) }}" placeholder="Contoh: Mohon cantumkan kode pesanan saat transfer">
                    </div>
                    <div class="form-check form-switch mb-4">
                        <input class="form-check-input" type="checkbox" name="show_customer_accounts" value="1" id="showCustAcc" {{ old('show_customer_accounts', $setting->show_customer_accounts) ? 'checked' : '' }}>
                        <label class="form-check-label small" for="showCustAcc">Tampilkan tombol Daftar / Masuk Akun di Katalog Pembeli</label>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="ph ph-floppy-disk"></i> Simpan Profil Toko
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Rekening Transfer Dinamis (Editable oleh Owner kapan saja jika terjadi error bank) -->
    <div class="col-lg-7">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="ph ph-bank text-accent me-2"></i>Daftar Rekening Bank &amp; E-Wallet Toko</span>
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addBankModal">
                    <i class="ph ph-plus"></i> Tambah Rekening
                </button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Nama Bank / E-Wallet</th>
                                <th>No. Rekening &amp; Atas Nama</th>
                                <th>Status &amp; Katalog</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($bankWallets as $bank)
                                <tr>
                                    <td>
                                        <div class="fw-bold text-dark">{{ $bank->name }}</div>
                                        <small class="text-muted text-uppercase">{{ $bank->type }}</small>
                                    </td>
                                    <td>
                                        @if($bank->account_number)
                                            <div class="fw-bold font-monospace text-dark">{{ $bank->account_number }}</div>
                                            <small class="text-muted">a.n. {{ $bank->account_holder ?: $setting->store_name }}</small>
                                        @else
                                            <span class="badge bg-warning bg-opacity-10 text-warning">Nomor belum diisi</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge {{ $bank->is_active ? 'bg-success bg-opacity-10 text-success' : 'bg-danger bg-opacity-10 text-danger' }}">
                                            {{ $bank->is_active ? 'Aktif' : 'Nonaktif' }}
                                        </span>
                                        @if($bank->is_active && $bank->show_on_catalog && $bank->account_number)
                                            <span class="badge bg-dark text-white ms-1">Tampil di Katalog</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-sm btn-light border" data-bs-toggle="modal" data-bs-target="#editBankModal{{ $bank->id }}">
                                            <i class="ph ph-pencil-simple text-accent"></i> Edit / Nonaktifkan
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center py-4 text-muted">Belum ada rekening bank. Klik Tambah Rekening di atas.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-white small text-muted">
                <i class="ph ph-info me-1"></i> Jika salah satu rekening bank mengalami gangguan/error, klik <strong>Edit / Nonaktifkan</strong> lalu matikan centang aktif/tampil agar tidak muncul ke pembeli.
            </div>
        </div>
    </div>
</div>

<!-- Modal Tambah Rekening -->
<div class="modal fade" id="addBankModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Tambah Rekening Bank / QRIS</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('finance.wallets.store') }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label">Nama Bank / E-Wallet</label>
                        <input type="text" name="name" class="form-control" placeholder="Contoh: Bank BCA / Bank Kalbar / BRI" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tipe</label>
                        <select name="type" class="form-select" required>
                            <option value="bank">Rekening Bank</option>
                            <option value="ewallet">E-Wallet / QRIS</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nomor Rekening</label>
                        <input type="text" name="account_number" class="form-control font-monospace" placeholder="Contoh: 1234567890" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Atas Nama Pemilik Rekening</label>
                        <input type="text" name="account_holder" class="form-control" value="{{ $setting->store_name }}" required>
                    </div>
                    <input type="hidden" name="initial_balance" value="0">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="show_on_catalog" value="1" id="addShowCatalog" checked>
                        <label class="form-check-label small" for="addShowCatalog">Tampilkan ke pembeli saat Checkout Transfer</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Rekening</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Rekening -->
@foreach($bankWallets as $bank)
<div class="modal fade" id="editBankModal{{ $bank->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Edit Rekening: {{ $bank->name }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('finance.wallets.update', $bank->id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label">Nama Bank / E-Wallet</label>
                        <input type="text" name="name" class="form-control" value="{{ $bank->name }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tipe</label>
                        <select name="type" class="form-select" required>
                            <option value="bank" {{ $bank->type === 'bank' ? 'selected' : '' }}>Rekening Bank</option>
                            <option value="ewallet" {{ $bank->type === 'ewallet' ? 'selected' : '' }}>E-Wallet / QRIS</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nomor Rekening</label>
                        <input type="text" name="account_number" class="form-control font-monospace" value="{{ $bank->account_number }}" placeholder="Isi nomor rekening asli">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Atas Nama</label>
                        <input type="text" name="account_holder" class="form-control" value="{{ $bank->account_holder ?: $setting->store_name }}">
                    </div>
                    <input type="hidden" name="initial_balance" value="{{ $bank->initial_balance }}">
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="bankActive{{ $bank->id }}" {{ $bank->is_active ? 'checked' : '' }}>
                        <label class="form-check-label small" for="bankActive{{ $bank->id }}">Rekening Aktif (matikan jika sedang gangguan/error)</label>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="show_on_catalog" value="1" id="bankCatalog{{ $bank->id }}" {{ $bank->show_on_catalog ? 'checked' : '' }}>
                        <label class="form-check-label small" for="bankCatalog{{ $bank->id }}">Tampilkan ke pembeli saat Checkout Transfer</label>
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
