@extends('layouts.app')

@section('title', 'Buat Perjalanan')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="d-flex align-items-center mb-4">
            <a href="{{ route('fleet.jobs.index') }}" class="btn btn-light border btn-sm me-3">
                <i class="ph ph-arrow-left"></i>
            </a>
            <h4 class="fw-bold mb-0" style="color: #1E293B;">Buat Perjalanan Baru</h4>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <form action="{{ route('fleet.jobs.store') }}" method="POST">
                    @csrf
                    
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Pilih Kendaraan</label>
                            <select class="form-select" name="vehicle_id" required>
                                <option value="">-- Pilih Kendaraan --</option>
                                @foreach($vehicles as $vehicle)
                                    <option value="{{ $vehicle->id }}">{{ $vehicle->plate_number }} - {{ $vehicle->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Pilih Driver</label>
                            <select class="form-select" name="driver_id" required>
                                <option value="">-- Pilih Driver --</option>
                                @foreach($drivers as $driver)
                                    <option value="{{ $driver->id }}">{{ $driver->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-8">
                            <label class="form-label fw-medium">Tujuan / Proyek</label>
                            <input type="text" class="form-control" name="destination" required placeholder="Contoh: Kirim Semen ke Proyek Pak Budi">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-medium">Waktu Berangkat</label>
                            <input type="datetime-local" class="form-control" name="departure_date" value="{{ date('Y-m-d\TH:i') }}" required>
                        </div>
                    </div>

                    <div class="bg-light p-3 rounded mb-4 border">
                        <h6 class="fw-bold mb-3"><i class="ph ph-check-square-offset text-primary me-2"></i> Ceklis Kendaraan Sebelum Jalan</h6>
                        <div class="row">
                            <div class="col-6 col-md-3 mb-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="cek_ban" id="cek_ban" checked>
                                    <label class="form-check-label" for="cek_ban">Ban & Roda</label>
                                </div>
                            </div>
                            <div class="col-6 col-md-3 mb-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="cek_dongkrak" id="cek_dongkrak" checked>
                                    <label class="form-check-label" for="cek_dongkrak">Dongkrak</label>
                                </div>
                            </div>
                            <div class="col-6 col-md-3 mb-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="cek_apar" id="cek_apar" checked>
                                    <label class="form-check-label" for="cek_apar">APAR</label>
                                </div>
                            </div>
                            <div class="col-6 col-md-3 mb-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="cek_lampu" id="cek_lampu" checked>
                                    <label class="form-check-label" for="cek_lampu">Lampu</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Pendapatan Perjalanan (Rp)</label>
                            <input type="number" class="form-control" name="revenue" min="0" value="0" placeholder="Isi 0 jika armada internal">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Biaya Operasional (BBM/Tol)</label>
                            <input type="number" class="form-control" name="operational_cost" min="0" value="0">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-medium">Catatan Tambahan</label>
                        <textarea class="form-control" name="notes" rows="2" placeholder="Opsional..."></textarea>
                    </div>

                    <hr class="mb-4">

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('fleet.jobs.index') }}" class="btn btn-light border">Batal</a>
                        <button type="submit" class="btn btn-primary">Mulai Perjalanan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection