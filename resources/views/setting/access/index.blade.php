@extends('layouts.app')

@section('title', 'Management Akses & Sandi')

@section('content')
<div class="page-header">
    <div>
        <h2 class="page-title">Management Akses & Sandi</h2>
        <p class="page-subtitle">Buat akun staff/kasir baru, atur hak akses (role), dan reset password karyawan.</p>
    </div>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createAccountModal">
        <i class="ph ph-user-plus"></i> Buat Akun Staff / Kasir
    </button>
</div>

<div class="alert alert-warning border-0 shadow-sm mb-4">
    <i class="ph ph-warning-circle me-2 fs-5 align-middle"></i>
    <strong>Perhatian:</strong> Hati-hati dalam mengubah Role. Akun dengan role <strong>Kasir</strong> otomatis masuk ke Workspace Khusus Kasir.
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Nama Karyawan</th>
                        <th>Email Login</th>
                        <th>Role Saat Ini</th>
                        <th>Status Login</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $user)
                    <tr>
                        <td class="fw-semibold text-dark">{{ $user->name }}</td>
                        <td><code>{{ $user->email }}</code></td>
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
                        <td>
                            @if($user->employee_status === 'nonaktif')
                                <span class="badge bg-danger bg-opacity-10 text-danger">Nonaktif</span>
                            @else
                                <span class="badge bg-success bg-opacity-10 text-success">Aktif</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="d-inline-flex gap-1 align-items-center">
                                @if($user->id !== auth()->id())
                                <form action="{{ route('setting.access.toggle', $user->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-light border {{ $user->employee_status === 'nonaktif' ? 'text-success' : 'text-danger' }}">
                                        {{ $user->employee_status === 'nonaktif' ? 'Aktifkan' : 'Nonaktifkan' }}
                                    </button>
                                </form>
                                @endif
                                <button type="button" class="btn btn-sm btn-light border" data-bs-toggle="modal" data-bs-target="#editAccessModal{{ $user->id }}">
                                    <i class="ph ph-key text-primary"></i> Ubah Akses / Reset Sandi
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @if($users->hasPages())
        <div class="card-footer bg-white">{{ $users->links() }}</div>
    @endif
</div>

<!-- Modal Buat Akun Baru -->
<div class="modal fade" id="createAccountModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Buat Akun Staff / Kasir Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('setting.access.store') }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label">Nama Lengkap</label>
                        <input type="text" name="name" class="form-control" placeholder="Contoh: Kasir Pagi" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email Login</label>
                        <input type="email" name="email" class="form-control" placeholder="Contoh: kasir2@sentosa.test" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role / Hak Akses</label>
                        <select name="role" class="form-select" required>
                            <option value="kasir" selected>Kasir (Workspace Khusus Kasir)</option>
                            <option value="admin">Admin / Kepala Toko</option>
                            <option value="gudang">Gudang</option>
                            <option value="driver">Driver</option>
                            <option value="karyawan">Karyawan Biasa</option>
                            <option value="owner">Owner</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Password</label>
                        <input type="text" name="password" class="form-control" placeholder="Minimal 4 karakter" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan & Buat Akun</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Access Modals -->
@foreach($users as $user)
<div class="modal fade" id="editAccessModal{{ $user->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Ubah Akses: {{ $user->name }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('setting.access.update', $user->id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label">Role / Jabatan</label>
                        <select class="form-select" name="role" required>
                            <option value="admin" {{ $user->role == 'admin' ? 'selected' : '' }}>Admin / Kepala Toko</option>
                            <option value="kasir" {{ $user->role == 'kasir' ? 'selected' : '' }}>Kasir</option>
                            <option value="gudang" {{ $user->role == 'gudang' ? 'selected' : '' }}>Gudang</option>
                            <option value="driver" {{ $user->role == 'driver' ? 'selected' : '' }}>Driver</option>
                            <option value="karyawan" {{ $user->role == 'karyawan' ? 'selected' : '' }}>Karyawan Biasa</option>
                            <option value="owner" {{ $user->role == 'owner' ? 'selected' : '' }}>Owner</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Reset Password / PIN Baru</label>
                        <input type="text" class="form-control" name="password" placeholder="Kosongkan jika tidak ingin diubah">
                        <small class="text-muted">Minimal 4 karakter.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach
@endsection
