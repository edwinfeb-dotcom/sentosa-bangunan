@extends('layouts.app')

@section('title', 'Master Pelanggan')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1" style="color: #1E293B;">Master Pelanggan</h4>
        <p class="text-muted mb-0" style="font-size: 0.875rem;">Kelola data pelanggan reguler, toko, dan kontraktor.</p>
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
                        <th width="5%">No</th>
                        <th>Nama Pelanggan</th>
                        <th>Tipe</th>
                        <th>Telepon</th>
                        <th>Status</th>
                        <th width="15%" class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($customers as $index => $customer)
                    <tr>
                        <td class="text-muted">{{ $customers->firstItem() + $index }}</td>
                        <td class="fw-semibold" style="color: #1E293B;">{{ $customer->name }}</td>
                        <td>
                            @if($customer->type == 'kontraktor')
                                <span class="badge bg-primary bg-opacity-10 text-primary">Kontraktor</span>
                            @elseif($customer->type == 'toko')
                                <span class="badge bg-info bg-opacity-10 text-info">Toko</span>
                            @else
                                <span class="badge bg-secondary bg-opacity-10 text-secondary">Reguler</span>
                            @endif
                        </td>
                        <td>{{ $customer->phone ?? '-' }}</td>
                        <td>
                            @if($customer->is_active)
                                <span class="badge bg-success bg-opacity-10 text-success">Aktif</span>
                            @else
                                <span class="badge bg-danger bg-opacity-10 text-danger">Nonaktif</span>
                            @endif
                        </td>
                        <td class="text-end">
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