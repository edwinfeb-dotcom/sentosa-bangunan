@extends('layouts.app')

@section('title', isset($category) ? 'Edit Kategori' : 'Tambah Kategori')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="d-flex align-items-center mb-4">
            <a href="{{ route('master.categories.index') }}" class="btn btn-light border btn-sm me-3">
                <i class="ph ph-arrow-left"></i>
            </a>
            <h4 class="fw-bold mb-0" style="color: #1E293B;">
                {{ isset($category) ? 'Edit Kategori' : 'Tambah Kategori' }}
            </h4>
        </div>

        <div class="card">
            <div class="card-body p-4">
                <form action="{{ isset($category) ? route('master.categories.update', $category->id) : route('master.categories.store') }}" method="POST">
                    @csrf
                    @if(isset($category))
                        @method('PUT')
                    @endif

                    <div class="mb-4">
                        <label for="name" class="form-label fw-medium">Nama Kategori</label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $category->name ?? '') }}" placeholder="Contoh: Semen, Cat, Besi" required autofocus>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1" {{ old('is_active', $category->is_active ?? true) ? 'checked' : '' }}>
                            <label class="form-check-label ms-2" for="is_active">Status Kategori Aktif</label>
                        </div>
                    </div>

                    <hr class="mb-4">

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('master.categories.index') }}" class="btn btn-light border">Batal</a>
                        <button type="submit" class="btn btn-primary">Simpan Data</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection