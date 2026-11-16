@extends('layouts.app')

@section('title', 'Proyek Kontraktor')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1" style="color: #1E293B;">Proyek Kontraktor</h4>
        <p class="text-muted mb-0" style="font-size: 0.875rem;">Kelola data proyek pelanggan kontraktor.</p>
    </div>
    <a href="{{ route('project.projects.create') }}" class="btn btn-primary d-flex align-items-center gap-2">
        <i class="ph ph-plus"></i> Tambah Proyek
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
                        <th>Nama Proyek</th>
                        <th>Pelanggan</th>
                        <th>Pagu Anggaran</th>
                        <th>Status</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($projects as $project)
                    <tr>
                        <td class="fw-semibold" style="color: #1E293B;">{{ $project->name }}</td>
                        <td>{{ $project->customer->name ?? '-' }}</td>
                        <td class="fw-medium">Rp {{ number_format($project->budget, 0, ',', '.') }}</td>
                        <td>
                            @if($project->status === 'active')
                                <span class="badge bg-success bg-opacity-10 text-success">Berjalan</span>
                            @elseif($project->status === 'completed')
                                <span class="badge bg-primary bg-opacity-10 text-primary">Selesai</span>
                            @else
                                <span class="badge bg-danger bg-opacity-10 text-danger">Batal</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <a href="{{ route('project.projects.edit', $project->id) }}" class="btn btn-sm btn-light border" title="Edit">
                                <i class="ph ph-pencil-simple text-primary"></i>
                            </a>
                            <form action="{{ route('project.projects.destroy', $project->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Yakin ingin menghapus proyek ini?')">
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
                            <i class="ph ph-briefcase fs-1 d-block mb-2 text-secondary opacity-50"></i>
                            Belum ada data proyek.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($projects->hasPages())
    <div class="card-footer bg-white border-top pt-3">
        {{ $projects->links() }}
    </div>
    @endif
</div>
@endsection