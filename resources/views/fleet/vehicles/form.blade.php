@extends('layouts.app')

@section('title', isset($vehicle) ? 'Edit Kendaraan' : 'Tambah Kendaraan')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="d-flex align-items-center mb-4">
            <a href="{{ route('fleet.vehicles.index') }}" class="btn btn-light border btn-sm me-3">
                <i class="ph ph-arrow-left"></i>
            </a>
            <h4 class="fw-bold mb-0" style="color: #1E293B;">
                {{ isset($vehicle) ? 'Edit Kendaraan' : 'Tambah Kendaraan Baru' }}
            </h4>
        </div>

        <div class="card">
            <div class="card-body p-4">
                <form action="{{ isset($vehicle) ? route('fleet.vehicles.update', $vehicle->id) : route('fleet.vehicles.store') }}" method="POST">
                    @csrf
                    @if(isset($vehicle))
                        @method('PUT')
                    @endif

                    <div class="mb-4">
                        <label class="form-label fw-medium">Nomor Polisi</label>
                        <input type="text" class="form-control @error('plate_number') is-invalid @enderror" name="plate_number" value="{{ old('plate_number', $vehicle->plate_number ?? '') }}" placeholder="Contoh: KB 1234 AB" required>
                        @error('plate_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-medium">Nama Kendaraan</label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name', $vehicle->name ?? '') }}" placeholder="Contoh: Truk Canter / Pickup L300" required>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-medium">Tipe Armada</label>
                        <select class="form-select @error('type') is-invalid @enderror" name="type" required>
                            <option value="internal" {{ old('type', $vehicle->type ?? '') == 'internal' ? 'selected' : '' }}>Internal Toko</option>
                            <option value="komersial" {{ old('type', $vehicle->type ?? '') == 'komersial' ? 'selected' : '' }}>Komersial (Rental)</option>
                            <option value="vendor" {{ old('type', $vehicle->type ?? '') == 'vendor' ? 'selected' : '' }}>Vendor Luar</option>
                        </select>
                        @error('type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label fw-medium small">Tgl Servis Terakhir</label>
                            <input type="date" class="form-control" name="last_service_at" value="{{ old('last_service_at', isset($vehicle) && $vehicle->last_service_at ? $vehicle->last_service_at->format('Y-m-d') : '') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-medium small">Jadwal Servis Berikut</label>
                            <input type="date" class="form-control" name="next_service_at" value="{{ old('next_service_at', isset($vehicle) && $vehicle->next_service_at ? $vehicle->next_service_at->format('Y-m-d') : '') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-medium small">Jatuh Tempo Pajak/KIR</label>
                            <input type="date" class="form-control" name="tax_due_at" value="{{ old('tax_due_at', isset($vehicle) && $vehicle->tax_due_at ? $vehicle->tax_due_at->format('Y-m-d') : '') }}">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-medium small">Catatan Servis / Oli / Ban</label>
                        <input type="text" class="form-control" name="service_notes" value="{{ old('service_notes', $vehicle->service_notes ?? '') }}" placeholder="Contoh: Ganti oli KM 45.000, kampas rem bagus">
                    </div>

                    <div class="mb-4">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1" {{ old('is_active', $vehicle->is_active ?? true) ? 'checked' : '' }}>
                            <label class="form-check-label ms-2" for="is_active">Status Kendaraan Aktif</label>
                        </div>
                    </div>

                    <hr class="mb-4">

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('fleet.vehicles.index') }}" class="btn btn-light border">Batal</a>
                        <button type="submit" class="btn btn-primary">Simpan Kendaraan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection