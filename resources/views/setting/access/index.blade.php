@extends('layouts.app')

@section('title', 'Management Akses & Sandi')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1" style="color: #1E293B;">Management Akses & Sandi</h4>
        <p class="text-muted mb-0" style="font-size: 0.875rem;">Atur hak akses (role) dan reset password/PIN karyawan.</p>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert">
        <i class="ph ph-check-circle me-2"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="alert alert-warning border-0 shadow-sm mb-4">
    <i class="ph ph-warning-circle me-2 fs-5 align-middle"></i> 
    <strong>Perhatian:</strong> Hati-hati dalam mengubah Role. Pastikan Anda memberikan akses yang sesuai dengan tugas karyawan.
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th class="ps-4">Nama Karyawan</th>
                        <th>Email</th>
                        <th>Role Saat Ini</th>
                        <th class="text-end pe-4">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $user)
                    <tr>
                        <td class="ps-4 fw-semibold" style="color: #1E293B;">{{ $user->name }}</td>
                        <td>{{ $user->email }}</td>
                        <td>
                            @if($user->role == 'owner')
                                <span class="badge bg-danger text-white">OWNER</span>
                            @elseif($user->role == 'admin')
                                <span class="badge bg-primary text-white">ADMIN</span>
                            @elseif($user->role == 'kasir')
                                <span class="badge bg-success text-white">KASIR</span>
                            @else
                                <span class="badge bg-secondary text-white text-uppercase">{{ $user->role }}</span>
                            @endif
                        </td>
                        <td class="text-end pe-4">
                            <!-- Tombol Edit Akses -->
                            <button class="btn btn-sm btn-light border" data-bs-toggle="modal" data-bs-target="#editAccessModal{{ $user->id }}">
                                <i class="ph ph-key text-primary me-1"></i> Ubah Akses
                            </button>

                            <!-- Modal Edit Akses -->
                            <div class="modal fade" id="editAccessModal{{ $user->id }}" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog text-start">
                                    <div class="modal-content border-0 shadow">
                                        <div class="modal-header border-bottom">
                                            <h5 class="modal-title fw-bold">Ubah Akses: {{ $user->name }}</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form action="{{ route('setting.access.update', $user->id) }}" method="POST">
                                            @csrf
                                            @method('PUT')
                                            <div class="modal-body p-4">
                                                <div class="mb-3">
                                                    <label class="form-label fw-medium">Role / Jabatan</label>
                                                    <select class="form-select" name="role" required>
                                                        <option value="admin" {{ $user->role == 'admin' ? 'selected' : '' }}>Admin / Kepala Toko</option>
                                                        <option value="kasir" {{ $user->role == 'kasir' ? 'selected' : '' }}>Kasir</option>
                                                        <option value="gudang" {{ $user->role == 'gudang' ? 'selected' : '' }}>Gudang</option>
                                                        <option value="driver" {{ $user->role == 'driver' ? 'selected' : '' }}>Driver</option>
                                                        <option value="karyawan" {{ $user->role == 'karyawan' ? 'selected' : '' }}>Karyawan Biasa</option>
                                                        <option value="owner" {{ $user->role == 'owner' ? 'selected' : '' }}>Owner</option>
                                                    </select>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label fw-medium">Reset Password / PIN Baru</label>
                                                    <input type="text" class="form-control" name="password" placeholder="Kosongkan jika tidak ingin diubah">
                                                    <small class="text-muted">Isi kolom ini jika karyawan lupa sandinya (minimal 4 karakter).</small>
                                                </div>
                                            </div>
                                            <div class="modal-footer border-top">
                                                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Batal</button>
                                                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @if($users->hasPages())
    <div class="card-footer bg-white border-top pt-3">
        {{ $users->links() }}
    </div>
    @endif
</div>
@endsection