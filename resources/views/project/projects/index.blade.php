@extends('layouts.app')

@section('title', 'Proyek & Material Kontraktor')

@section('content')
<div class="page-header">
    <div>
        <h2 class="page-title">Proyek Kontraktor & Material</h2>
        <p class="page-subtitle">Pantau pagu anggaran kontraktor dan realisasi pemakaian material toko.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <button type="button" class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#importProjectsModal">
            <i class="ph ph-upload-simple"></i> Import Excel Proyek
        </button>
        <a href="{{ route('project.projects.create') }}" class="btn btn-primary">
            <i class="ph ph-plus"></i> Tambah Proyek
        </a>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Nama Proyek</th>
                        <th>Kontraktor</th>
                        <th>Pagu Anggaran</th>
                        <th>Realisasi Material</th>
                        <th>Status</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($projects as $project)
                    @php
                        $used = (float) ($project->materials_sum_subtotal ?? 0);
                        $pct = $project->budget > 0 ? min(100, round(($used / $project->budget) * 100, 1)) : 0;
                    @endphp
                    <tr>
                        <td>
                            <div class="fw-bold text-dark">{{ $project->name }}</div>
                            <small class="text-muted">Mulai: {{ $project->start_date->format('d/m/Y') }}</small>
                        </td>
                        <td>
                            @if($project->customer)
                                <span class="fw-medium">{{ $project->customer->name }}</span>
                            @else
                                <span class="badge bg-dark text-white">Proyek Internal Owner</span>
                            @endif
                        </td>
                        <td class="fw-semibold">Rp {{ number_format($project->budget, 0, ',', '.') }}</td>
                        <td style="min-width: 190px;">
                            <div class="d-flex justify-content-between small mb-1">
                                <span class="fw-semibold text-primary">Rp {{ number_format($used, 0, ',', '.') }}</span>
                                <span class="text-muted">{{ $pct }}%</span>
                            </div>
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar {{ $pct >= 90 ? 'bg-danger' : 'bg-primary' }}" style="width: {{ $pct }}%;"></div>
                            </div>
                        </td>
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
                            <a href="{{ route('project.projects.show', $project->id) }}" class="btn btn-sm btn-primary" title="Realisasi Material">
                                <i class="ph ph-bricks"></i> Material
                            </a>
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
                        <td colspan="6" class="text-center py-5 text-muted">
                            <i class="ph ph-briefcase fs-1 d-block mb-2 opacity-50"></i>
                            Belum ada data proyek.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($projects->hasPages())
        <div class="card-footer bg-white">{{ $projects->links() }}</div>
    @endif
</div>

<div class="modal fade" id="importProjectsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Import Master Proyek dari Excel</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('project.projects.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body p-4">
                    <div class="alert alert-info border-0 small mb-3">
                        <div class="fw-bold mb-1">Mendukung File <code>MASTER PROJECT SENTOSA (.xlsx)</code>:</div>
                        Sistem hanya mem-parsing sheet <code>MASTER</code> (<code>NAMA</code>, <code>SUMBER</code>, <code>DEPO</code>, <code>DEBET</code>, <code>SALDO</code>) sehingga tetap ringan dan tidak membebani memori server.
                    </div>
                    <label class="form-label fw-semibold">Pilih File Excel (.xlsx, .xlsm, .xls, .csv)</label>
                    <input type="file" name="file" class="form-control" accept=".xlsx,.xlsm,.xls,.csv" required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success"><i class="ph ph-upload-simple"></i> Upload &amp; Import</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
