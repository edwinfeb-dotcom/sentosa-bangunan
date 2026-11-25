@extends('layouts.app')

@section('title', 'Master Pelanggan')

@section('content')
<div class="page-header">
    <div>
        <h2 class="page-title">Master Pelanggan</h2>
        <p class="page-subtitle">Kelola data pelanggan, Bintang Gold loyalitas, dan saldo deposit.</p>
    </div>
    <a href="{{ route('master.customers.create') }}" class="btn btn-primary">
        <i class="ph ph-plus"></i> Tambah Pelanggan
    </a>
</div>

<!-- Search & Filter -->
<div class="card mb-4">
    <div class="card-body p-3">
        <form action="{{ route('master.customers.index') }}" method="GET" class="row g-2 align-items-end">
            <div class="col-md-5">
                <label class="form-label">Cari Nama atau No. Telepon</label>
                <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Ketik nama atau nomor HP...">
            </div>
            <div class="col-md-4">
                <label class="form-label">Tipe Pelanggan</label>
                <select name="type" class="form-select">
                    <option value="">Semua Tipe</option>
                    <option value="reguler" {{ request('type') == 'reguler' ? 'selected' : '' }}>Reguler</option>
                    <option value="toko" {{ request('type') == 'toko' ? 'selected' : '' }}>Toko</option>
                    <option value="kontraktor" {{ request('type') == 'kontraktor' ? 'selected' : '' }}>Kontraktor</option>
                </select>
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
                        <th>Saldo Deposit</th>
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
                        <td class="fw-semibold">Rp {{ number_format($customer->deposit_balance, 0, ',', '.') }}</td>
                        <td>
                            @if($customer->is_active)
                                <span class="badge bg-success bg-opacity-10 text-success">Aktif</span>
                            @else
                                <span class="badge bg-danger bg-opacity-10 text-danger">Nonaktif</span>
                            @endif
                        </td>
                        <td class="text-end">
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
@endsection
