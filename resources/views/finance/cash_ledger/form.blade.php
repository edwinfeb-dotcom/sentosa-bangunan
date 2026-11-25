@extends('layouts.app')

@section('title', 'Catat Kas Baru')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-7 col-lg-6">
        <div class="d-flex align-items-center gap-3 mb-4">
            <a href="{{ route('finance.cash-ledger.index') }}" class="btn btn-light border btn-sm">
                <i class="ph ph-arrow-left"></i>
            </a>
            <h4 class="fw-bold mb-0 text-dark">Catat Transaksi Kas Baru</h4>
        </div>

        <div class="card">
            <div class="card-body p-4">
                <form action="{{ route('finance.cash-ledger.store') }}" method="POST">
                    @csrf
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Tipe Transaksi</label>
                            <select class="form-select" name="type" required>
                                <option value="in" {{ old('type') == 'in' ? 'selected' : '' }}>Kas Masuk (Pemasukan)</option>
                                <option value="out" {{ old('type') == 'out' ? 'selected' : '' }}>Kas Keluar (Pengeluaran)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Dompet / Rekening</label>
                            <select class="form-select" name="wallet_id">
                                @foreach($wallets as $wallet)
                                    <option value="{{ $wallet->id }}">{{ $wallet->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Kategori Arus Kas</label>
                            <select class="form-select" name="category">
                                <option value="operasional">Biaya / Kas Operasional</option>
                                <option value="penjualan">Penjualan</option>
                                <option value="pembelian_supplier">Pembelian Supplier</option>
                                <option value="gaji">Gaji / Kasbon</option>
                                <option value="armada">BBM / Perjalanan Armada</option>
                                <option value="lainnya">Lain-lain</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tanggal</label>
                            <input type="date" class="form-control" name="date" value="{{ old('date', date('Y-m-d')) }}" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Nominal (Rp)</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0">Rp</span>
                            <input type="number" class="form-control border-start-0" name="amount" value="{{ old('amount') }}" required min="1">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Keterangan</label>
                        <textarea class="form-control" name="description" rows="3" required placeholder="Contoh: Beli air galon, bayar listrik toko...">{{ old('description') }}</textarea>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('finance.cash-ledger.index') }}" class="btn btn-light border">Batal</a>
                        <button type="submit" class="btn btn-primary">Simpan Data</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
