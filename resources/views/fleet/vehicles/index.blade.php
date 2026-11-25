@extends('layouts.app')

@section('title', 'Armada Kendaraan')

@section('content')
<div class="page-header">
    <div>
        <h2 class="page-title">Armada Kendaraan</h2>
        <p class="page-subtitle">Kelola data kendaraan operasional, komersial, dan vendor.</p>
    </div>
    <a href="{{ route('fleet.vehicles.create') }}" class="btn btn-primary">
        <i class="ph ph-plus"></i> Tambah Kendaraan
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
                        <th>No Polisi</th>
                        <th>Nama Kendaraan</th>
                        <th>Tipe Armada</th>
                        <th>Status</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($vehicles as $vehicle)
                    <tr>
                        <td>
                            <span class="badge bg-dark font-monospace fs-6">{{ $vehicle->plate_number }}</span>
                        </td>
                        <td class="fw-semibold" style="color: #1E293B;">{{ $vehicle->name }}</td>
                        <td>
                            @if($vehicle->type == 'internal')
                                <span class="badge bg-primary bg-opacity-10 text-primary">Internal</span>
                            @elseif($vehicle->type == 'komersial')
                                <span class="badge bg-success bg-opacity-10 text-success">Komersial</span>
                            @else
                                <span class="badge bg-warning bg-opacity-10 text-warning">Vendor Luar</span>
                            @endif
                        </td>
                        <td>
                            @if($vehicle->is_active)
                                <span class="badge bg-success bg-opacity-10 text-success">Aktif</span>
                            @else
                                <span class="badge bg-danger bg-opacity-10 text-danger">Nonaktif</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <a href="{{ route('fleet.vehicles.edit', $vehicle->id) }}" class="btn btn-sm btn-light border" title="Edit">
                                <i class="ph ph-pencil-simple text-primary"></i>
                            </a>
                            <form action="{{ route('fleet.vehicles.destroy', $vehicle->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Yakin ingin menghapus kendaraan ini?')">
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
                        <td colspan="5" class="text-center py-5 text-muted">
                            <i class="ph ph-truck fs-1 d-block mb-2 text-secondary opacity-50"></i>
                            Belum ada data kendaraan.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($vehicles->hasPages())
    <div class="card-footer bg-white border-top pt-3">
        {{ $vehicles->links() }}
    </div>
    @endif
</div>
@endsection