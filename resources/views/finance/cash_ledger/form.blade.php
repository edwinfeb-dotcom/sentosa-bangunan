@extends('layouts.app')

@section('title', 'Catat Kas Baru')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="d-flex align-items-center mb-4">
            <a href="{{ route('finance.cash-ledger.index') }}" class="btn btn-light border btn-sm me-3">
                <i class="ph ph-arrow-left"></i>
            </a>
            <h4 class="fw-bold mb-0" style="color: #1E293B;">Catat Kas Baru</h4>
        </div>

        <div class="card">
            <div class="card-body p-4">
                <form action="{{ route('finance.cash-ledger.store') }}" method="POST">
                    @csrf
                    <div class="mb-4">
                        <label class="form-label fw-medium">Tipe Transaksi</label>
                        <select class="form-select @error('type') is-invalid @enderror" name="type" required>
                            <option value="in" {{ old('type') == 'in' ? 'selected' : '' }}>Kas Masuk (Pemasukan)</option>
                            <option value="out" {{ old('type') == 'out' ? 'selected' : '' }}>Kas Keluar (Pengeluaran)</option>
                        </select>
                        @error('type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-medium">Tanggal</label>
                        <input type="date" class="form-control @error('date') is-invalid @enderror" name="date" value="{{ old('date', date('Y-m-d')) }}" required>
                        @error('date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-medium">Nominal</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0">Rp</span>
                            <input type="number" class="form-control border-start-0 @error('amount') is-invalid @enderror" name="amount" value="{{ old('amount') }}" required min="1">
                        </div>
                        @error('amount') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-medium">Keterangan</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" name="description" rows="3" required placeholder="Contoh: Beli air minum kantor">{{ old('description') }}</textarea>
                        @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <hr class="mb-4">

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