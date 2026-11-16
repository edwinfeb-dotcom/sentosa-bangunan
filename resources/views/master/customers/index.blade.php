@extends('layouts.app')

@section('title', 'Master Pelanggan')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1" style="color: #1E293B;">Master Pelanggan</h4>
        <p class="text-muted mb-0" style="font-size: 0.875rem;">Kelola data pelanggan, loyalitas (Bintang), dan Deposit.</p>
    </div>
    <a href="{{ route('master.customers.create') }}" class="btn btn-primary d-flex align-items-center gap-2">
        <i class="ph ph-plus"></i> Tambah Pelanggan
    </a>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert">
        <i class="ph ph-check-circle me-2"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th class="ps-4">Nama Pelanggan</th>
                        <th>Tipe</th>
                        <th>Bintang Gold</th>
                        <th>Saldo Deposit</th>
                        <th>Status</th>
                        <th class="text-end pe-4">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($customers as $index => $customer)
                    <tr>
                        <td class="ps-4 fw-semibold" style="color: #1E293B;">
                            {{ $customer->name }}
                            <div class="text-muted fw-normal" style="font-size: 0.8rem;">{{ $customer->phone ?? '-' }}</div>
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
                            <span class="badge bg-warning text-dark d-flex align-items-center gap-1" style="width: fit-content;">
                                <i class="ph-fill ph-star text-warning"></i> {{ $customer->points }}
                            </span>
                        </td>
                        <td class="fw-medium">Rp {{ number_format($customer->deposit_balance, 0, ',', '.') }}</td>
                        <td>
                            @if($customer->is_active)
                                <span class="badge bg-success bg-opacity-10 text-success">Aktif</span>
                            @else
                                <span class="badge bg-danger bg-opacity-10 text-danger">Nonaktif</span>
                            @endif
                        </td>
                        <td class="text-end pe-4">
                            <!-- Tombol Top Up Deposit -->
                            <button class="btn btn-sm btn-light border text-success" data-bs-toggle="modal" data-bs-target="#depositModal{{ $customer->id }}" title="Top Up Deposit">
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

                    <!-- Modal Top Up Deposit -->
                    <div class="modal fade" id="depositModal{{ $customer->id }}" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content border-0 shadow">
                                <div class="modal-header">
                                    <h5 class="modal-title fw-bold">Top Up Deposit: {{ $customer->name }}</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <form action="{{ route('master.customers.deposit', $customer->id) }}" method="POST">
                                    @csrf
                                    <div class="modal-body p-4">
                                        <div class="mb-3">
                                            <label class="form-label fw-medium">Nominal Titipan (Rp)</label>
                                            <input type="number" name="amount" class="form-control form-control-lg" required min="1">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label fw-medium">Keterangan</label>
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
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            <i class="ph ph-users fs-1 d-block mb-2 text-secondary opacity-50"></i>
                            Belum ada data pelanggan.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($customers->hasPages())
    <div class="card-footer bg-white border-top pt-3">
        {{ $customers->links() }}
    </div>
    @endif
</div>
@endsection