@extends('layouts.app')

@section('title', 'Buka Kasir')

@section('content')
<div class="row justify-content-center align-items-center" style="min-height: 60vh;">
    <div class="col-md-5">
        <div class="card text-center">
            <div class="card-body p-5">
                <div class="mb-4">
                    <div class="d-inline-flex align-items-center justify-content-center rounded-3 border mb-2" style="width: 72px; height: 72px; background-color: var(--sb-accent-soft); border-color: var(--sb-accent-border) !important; color: var(--sb-accent);">
                        <i class="ph ph-lock-key-open fs-1"></i>
                    </div>
                </div>
                <h4 class="fw-bold mb-2 text-dark" style="letter-spacing: -0.02em;">Buka Sesi Kasir</h4>
                <p class="text-muted small mb-4">Masukkan saldo awal (uang kecil) di laci kasir sebelum memulai transaksi.</p>

                <form action="{{ route('pos.session.open') }}" method="POST">
                    @csrf
                    <div class="mb-4 text-start">
                        <label class="form-label fw-medium">Saldo Awal (Rp)</label>
                        <input type="number" name="opening_balance" class="form-control form-control-lg tabular-nums @error('opening_balance') is-invalid @enderror" value="{{ old('opening_balance', 0) }}" required min="0">
                        @error('opening_balance')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <button type="submit" class="btn btn-primary w-100 btn-lg">
                        Buka Kasir Sekarang
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
