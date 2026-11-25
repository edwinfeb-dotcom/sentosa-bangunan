@extends('layouts.app')

@section('title', isset($employee) ? 'Edit Karyawan' : 'Tambah Karyawan')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-9">
        <div class="d-flex align-items-center mb-4">
            <a href="{{ route('hr.employees.index') }}" class="btn btn-light border btn-sm me-3">
                <i class="ph ph-arrow-left"></i>
            </a>
            <h4 class="fw-bold mb-0" style="color: #1E293B;">
                {{ isset($employee) ? 'Edit Data Karyawan' : 'Tambah Karyawan Baru' }}
            </h4>
        </div>

        <div class="card">
            <div class="card-body p-4">
                <form action="{{ isset($employee) ? route('hr.employees.update', $employee->id) : route('hr.employees.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @if(isset($employee)) @method('PUT') @endif

                    <!-- Data Akun & Foto -->
                    <h6 class="fw-bold text-primary mb-3">A. Data Akun & Foto</h6>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-medium">Foto Karyawan (Max 2MB)</label>
                            <input type="file" class="form-control" name="photo" accept="image/*">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-medium">Nama Lengkap</label>
                            <input type="text" class="form-control" name="name" value="{{ old('name', $employee->name ?? '') }}" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-medium">Email</label>
                            <input type="email" class="form-control" name="email" value="{{ old('email', $employee->email ?? '') }}" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-medium">Password {{ isset($employee) ? '(Kosongkan jika tidak diubah)' : '' }}</label>
                            <input type="password" class="form-control" name="password" {{ isset($employee) ? '' : 'required' }}>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-medium">Hak Akses (Role)</label>
                            <select class="form-select" name="role" required>
                                <option value="admin" {{ old('role', $employee->role ?? '') == 'admin' ? 'selected' : '' }}>Admin / Kepala Toko</option>
                                <option value="kasir" {{ old('role', $employee->role ?? '') == 'kasir' ? 'selected' : '' }}>Kasir</option>
                                <option value="gudang" {{ old('role', $employee->role ?? '') == 'gudang' ? 'selected' : '' }}>Gudang</option>
                                <option value="driver" {{ old('role', $employee->role ?? '') == 'driver' ? 'selected' : '' }}>Driver</option>
                                <option value="karyawan" {{ old('role', $employee->role ?? '') == 'karyawan' ? 'selected' : '' }}>Karyawan Biasa</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-medium">Status Karyawan</label>
                            <select class="form-select" name="employee_status">
                                <option value="aktif" {{ old('employee_status', $employee->employee_status ?? '') == 'aktif' ? 'selected' : '' }}>Aktif</option>
                                <option value="training" {{ old('employee_status', $employee->employee_status ?? '') == 'training' ? 'selected' : '' }}>Training</option>
                                <option value="nonaktif" {{ old('employee_status', $employee->employee_status ?? '') == 'nonaktif' ? 'selected' : '' }}>Nonaktif / Resign</option>
                            </select>
                        </div>
                    </div>

                    <hr class="my-4">

                    <!-- Data Pribadi -->
                    <h6 class="fw-bold text-primary mb-3">B. Data Pribadi</h6>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-medium">Tempat Lahir</label>
                            <input type="text" class="form-control" name="birth_place" value="{{ old('birth_place', $employee->birth_place ?? '') }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-medium">Tanggal Lahir</label>
                            <input type="date" class="form-control" name="birth_date" value="{{ old('birth_date', isset($employee) && $employee->birth_date ? $employee->birth_date->format('Y-m-d') : '') }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-medium">Nomor HP / WhatsApp</label>
                            <input type="text" class="form-control" name="phone" value="{{ old('phone', $employee->phone ?? '') }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-medium">Asal Daerah</label>
                            <input type="text" class="form-control" name="origin" value="{{ old('origin', $employee->origin ?? '') }}">
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label fw-medium">Alamat Tempat Tinggal</label>
                            <textarea class="form-control" name="address" rows="2">{{ old('address', $employee->address ?? '') }}</textarea>
                        </div>
                    </div>

                    <hr class="my-4">

                    <!-- Data Kepegawaian -->
                    <h6 class="fw-bold text-primary mb-3">C. Data Kepegawaian</h6>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-medium">Kategori Karyawan</label>
                            <select class="form-select" name="employee_category">
                                <option value="karyawan tetap" {{ old('employee_category', $employee->employee_category ?? '') == 'karyawan tetap' ? 'selected' : '' }}>Karyawan Tetap</option>
                                <option value="kontrak" {{ old('employee_category', $employee->employee_category ?? '') == 'kontrak' ? 'selected' : '' }}>Kontrak</option>
                                <option value="harian" {{ old('employee_category', $employee->employee_category ?? '') == 'harian' ? 'selected' : '' }}>Harian</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-medium">Tanggal Masuk</label>
                            <input type="date" class="form-control" name="join_date" value="{{ old('join_date', isset($employee) && $employee->join_date ? $employee->join_date->format('Y-m-d') : date('Y-m-d')) }}" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-medium">Gaji Harian / Lembur (Rp)</label>
                            <input type="number" class="form-control" name="daily_salary" value="{{ old('daily_salary', $employee->daily_salary ?? 100000) }}" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-medium">Gaji Full Bulanan (Rp)</label>
                            <input type="number" class="form-control" name="monthly_salary" value="{{ old('monthly_salary', $employee->monthly_salary ?? 0) }}" min="0" placeholder="0 = pakai hitungan HOK harian">
                            <div class="form-text small">Jika &gt; 0, gaji bulanan full dipakai &amp; dipotong jika Bolos/Alpha.</div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-medium">Potongan per Hari Alpha / Bolos (Rp)</label>
                            <input type="number" class="form-control" name="absence_deduction_per_day" value="{{ old('absence_deduction_per_day', $employee->absence_deduction_per_day ?? 0) }}" min="0" placeholder="0 = otomatis Gaji Full / 26">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-medium">Diskon Karyawan (%)</label>
                            <input type="number" step="0.1" class="form-control" name="employee_discount" value="{{ old('employee_discount', $employee->employee_discount ?? 0) }}">
                        </div>
                    </div>

                    <hr class="mb-4">

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('hr.employees.index') }}" class="btn btn-light border">Batal</a>
                        <button type="submit" class="btn btn-primary">Simpan Data Karyawan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection