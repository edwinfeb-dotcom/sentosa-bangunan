@extends('layouts.app')

@section('title', 'Management Karyawan')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1" style="color: #1E293B;">Management Karyawan</h4>
        <p class="text-muted mb-0" style="font-size: 0.875rem;">Kelola identitas, foto, dan status kepegawaian.</p>
    </div>
    <a href="{{ route('hr.employees.create') }}" class="btn btn-primary d-flex align-items-center gap-2">
        <i class="ph ph-plus"></i> Tambah Karyawan
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
                        <th class="ps-4">Karyawan</th>
                        <th>Kategori</th>
                        <th>Jabatan (Role)</th>
                        <th>No. HP</th>
                        <th>Tgl Masuk</th>
                        <th>Status</th>
                        <th class="text-end pe-4">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($employees as $emp)
                    <tr>
                        <td class="ps-4">
                            <div class="d-flex align-items-center gap-3">
                                @if($emp->photo)
                                    <img src="{{ asset('storage/' . $emp->photo) }}" alt="Foto" class="rounded-circle object-fit-cover" style="width: 40px; height: 40px;">
                                @else
                                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 40px; height: 40px;">
                                        {{ substr($emp->name, 0, 1) }}
                                    </div>
                                @endif
                                <div>
                                    <div class="fw-semibold" style="color: #1E293B;">{{ $emp->name }}</div>
                                    <small class="text-muted">{{ $emp->email }}</small>
                                </div>
                            </div>
                        </td>
                        <td class="text-capitalize">{{ $emp->employee_category }}</td>
                        <td class="text-capitalize">{{ $emp->role }}</td>
                        <td>{{ $emp->phone ?? '-' }}</td>
                        <td>{{ $emp->join_date ? $emp->join_date->format('d/m/Y') : '-' }}</td>
                        <td>
                            @if($emp->employee_status === 'aktif')
                                <span class="badge bg-success bg-opacity-10 text-success">Aktif</span>
                            @elseif($emp->employee_status === 'training')
                                <span class="badge bg-warning bg-opacity-10 text-warning">Training</span>
                            @else
                                <span class="badge bg-danger bg-opacity-10 text-danger">Nonaktif</span>
                            @endif
                        </td>
                        <td class="text-end pe-4">
                            <a href="{{ route('hr.employees.edit', $emp->id) }}" class="btn btn-sm btn-light border" title="Edit">
                                <i class="ph ph-pencil-simple text-primary"></i>
                            </a>
                            <form action="{{ route('hr.employees.destroy', $emp->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Yakin ingin menghapus karyawan ini?')">
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
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="ph ph-users fs-1 d-block mb-2 text-secondary opacity-50"></i>
                            Belum ada data karyawan.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($employees->hasPages())
    <div class="card-footer bg-white border-top pt-3">
        {{ $employees->links() }}
    </div>
    @endif
</div>
@endsection