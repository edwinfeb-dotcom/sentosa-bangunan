@extends('layouts.app')

@section('title', 'Master Form Job Desk & Ceklis')

@section('content')
<div class="page-header">
    <div>
        <h2 class="page-title">Master Form Job Desk & Ceklis</h2>
        <p class="page-subtitle">Atur daftar tugas piket, pemeriksaan aset, dan ceklis pagi/sore secara dinamis untuk Laporan Harian.</p>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">Tambah Item Ceklis Baru</div>
            <div class="card-body p-4">
                <form action="{{ route('setting.checklists.store') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Bagian Laporan</label>
                        <select name="section" class="form-select" required>
                            @foreach($sections as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Judul Tugas / Ceklis</label>
                        <input type="text" name="title" class="form-control" placeholder="Contoh: Cek tekanan ban armada" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Deskripsi Singkat (Opsional)</label>
                        <input type="text" name="description" class="form-control" placeholder="Penjelasan singkat tugas">
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Urutan Tampil</label>
                        <input type="number" name="sort_order" class="form-control" value="1" min="1">
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Tambah Item Ceklis</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        @foreach($sections as $secKey => $secLabel)
            <div class="card mb-3">
                <div class="card-header">{{ $secLabel }}</div>
                <div class="card-body p-0">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th width="8%">Urut</th>
                                <th>Judul Tugas</th>
                                <th>Status</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($templates[$secKey] ?? [] as $item)
                                <tr>
                                    <td class="text-muted">{{ $item->sort_order }}</td>
                                    <td>
                                        <div class="fw-semibold text-dark">{{ $item->title }}</div>
                                        @if($item->description)<small class="text-muted">{{ $item->description }}</small>@endif
                                    </td>
                                    <td>
                                        <span class="badge {{ $item->is_active ? 'bg-success bg-opacity-10 text-success' : 'bg-secondary bg-opacity-10 text-secondary' }}">
                                            {{ $item->is_active ? 'Aktif' : 'Nonaktif' }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-sm btn-light border" data-bs-toggle="modal" data-bs-target="#editChecklistModal{{ $item->id }}">
                                            <i class="ph ph-pencil-simple text-primary"></i>
                                        </button>
                                        <form action="{{ route('setting.checklists.destroy', $item->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus item ceklis ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-light border"><i class="ph ph-trash text-danger"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center py-3 text-muted small">Belum ada item ceklis di bagian ini.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endforeach
    </div>
</div>

<!-- Modals -->
@foreach($templates->flatten() as $item)
<div class="modal fade" id="editChecklistModal{{ $item->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Edit Item Ceklis</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('setting.checklists.update', $item->id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label">Judul Tugas</label>
                        <input type="text" name="title" class="form-control" value="{{ $item->title }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Deskripsi</label>
                        <input type="text" name="description" class="form-control" value="{{ $item->description }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Urutan</label>
                        <input type="number" name="sort_order" class="form-control" value="{{ $item->sort_order }}" min="0">
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="chkActive{{ $item->id }}" {{ $item->is_active ? 'checked' : '' }}>
                        <label class="form-check-label" for="chkActive{{ $item->id }}">Aktif di Form Laporan Harian</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach
@endsection
