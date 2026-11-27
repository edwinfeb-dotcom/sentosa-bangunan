@extends('layouts.app')

@section('title', 'Management Karyawan')

@section('content')
<div class="page-header">
    <div>
        <h2 class="page-title">Management Karyawan</h2>
        <p class="page-subtitle">Kelola identitas, foto, gaji harian, dan status kepegawaian.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <button type="button" class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#importEmployeesModal">
            <i class="ph ph-upload-simple"></i> Import Excel / Laporan Harian
        </button>
        <a href="{{ route('hr.employees.create') }}" class="btn btn-primary">
            <i class="ph ph-plus"></i> Tambah Karyawan
        </a>
    </div>
</div>

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
                                    <img src="{{ asset('storage/' . $emp->photo) }}" alt="Foto" class="rounded-circle object-fit-cover" style="width: 40px; height: 40px;" loading="lazy" decoding="async">
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

<div class="modal fade" id="importEmployeesModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Import Karyawan &amp; Kasbon Aktif</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('hr.employees.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body p-4">
                    <div class="alert alert-info border-0 small mb-3">
                        <div class="fw-bold mb-1">Mendukung File <code>LAPORAN HARIAN SENTOSA (.xlsm / .xlsx)</code>:</div>
                        Otomatis mengambil daftar karyawan aktif/nonaktif, estimasi upah harian, dan sisa kredit/kasbon aktif dari sheet <code>MANAGEMENT</code> (atau tabel kolom <code>NAMA</code>, <code>ROLE</code>, <code>GAJI HARIAN</code>). Akun baru memakai sandi awal <code>password123</code>.
                    </div>
                    <label class="form-label fw-semibold">Pilih File Excel (.xlsm, .xlsx, .xls, .csv)</label>
                    <input type="file" name="file" class="form-control" accept=".xlsm,.xlsx,.xls,.csv" required>
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