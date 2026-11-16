@extends('layouts.app')

@section('title', isset($project) ? 'Edit Proyek' : 'Tambah Proyek')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="d-flex align-items-center mb-4">
            <a href="{{ route('project.projects.index') }}" class="btn btn-light border btn-sm me-3">
                <i class="ph ph-arrow-left"></i>
            </a>
            <h4 class="fw-bold mb-0" style="color: #1E293B;">
                {{ isset($project) ? 'Edit Proyek' : 'Tambah Proyek Baru' }}
            </h4>
        </div>

        <div class="card">
            <div class="card-body p-4">
                <form action="{{ isset($project) ? route('project.projects.update', $project->id) : route('project.projects.store') }}" method="POST">
                    @csrf
                    @if(isset($project))
                        @method('PUT')
                    @endif
                    
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <label class="form-label fw-medium">Pelanggan (Kontraktor)</label>
                            <select class="form-select @error('customer_id') is-invalid @enderror" name="customer_id" required>
                                <option value="">-- Pilih Pelanggan --</option>
                                @foreach($customers as $customer)
                                    <option value="{{ $customer->id }}" {{ old('customer_id', $project->customer_id ?? '') == $customer->id ? 'selected' : '' }}>{{ $customer->name }}</option>
                                @endforeach
                            </select>
                            @error('customer_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6 mb-4">
                            <label class="form-label fw-medium">Nama Proyek</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name', $project->name ?? '') }}" placeholder="Contoh: Renovasi Ruko" required>
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-4">
                            <label class="form-label fw-medium">Pagu Anggaran</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0">Rp</span>
                                <input type="number" class="form-control border-start-0" name="budget" value="{{ old('budget', $project->budget ?? 0) }}" required min="0">
                            </div>
                        </div>
                        <div class="col-md-4 mb-4">
                            <label class="form-label fw-medium">Tanggal Mulai</label>
                            <input type="date" class="form-control" name="start_date" value="{{ old('start_date', isset($project) ? $project->start_date->format('Y-m-d') : date('Y-m-d')) }}" required>
                        </div>
                        <div class="col-md-4 mb-4">
                            <label class="form-label fw-medium">Tanggal Selesai</label>
                            <input type="date" class="form-control" name="end_date" value="{{ old('end_date', isset($project) && $project->end_date ? $project->end_date->format('Y-m-d') : '') }}">
                            <small class="text-muted" style="font-size: 0.75rem;">*Bisa dikosongkan jika belum tahu</small>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-medium">Status Proyek</label>
                        <select class="form-select" name="status" required>
                            <option value="active" {{ old('status', $project->status ?? '') == 'active' ? 'selected' : '' }}>Berjalan (Aktif)</option>
                            <option value="completed" {{ old('status', $project->status ?? '') == 'completed' ? 'selected' : '' }}>Selesai</option>
                            <option value="cancelled" {{ old('status', $project->status ?? '') == 'cancelled' ? 'selected' : '' }}>Batal</option>
                        </select>
                    </div>

                    <hr class="mb-4">

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('project.projects.index') }}" class="btn btn-light border">Batal</a>
                        <button type="submit" class="btn btn-primary">Simpan Proyek</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection