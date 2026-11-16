@extends('layouts.app')

@section('title', 'Buka Kasir')

@section('content')
<div class="row justify-content-center align-items-center" style="min-height: 60vh;">
    <div class="col-md-5">
        <div class="card text-center">
            <div class="card-body p-5">
                <div class="mb-4">
                    <i class="ph ph-lock-key-open" style="font-size: 4rem; color: #1E3A8A;"></i>
                </div>
                <h4 class="fw-bold mb-3" style="color: #1E3A8A;">Buka Sesi Kasir</h4>
                <p class="text-muted mb-4">Masukkan saldo awal (uang kecil) di laci kasir sebelum memulai transaksi.</p>

                <form action="{{ route('pos.session.open') }}" method="POST">
                    @csrf
                    <div class="mb-4 text-start">
                        <label class="form-label fw-medium">Saldo Awal (Rp)</label>
                        <input type="number" name="opening_balance" class="form-control form-control-lg @error('opening_balance') is-invalid @enderror" value="{{ old('opening_balance', 0) }}" required min="0">
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