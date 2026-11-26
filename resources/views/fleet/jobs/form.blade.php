@extends('layouts.app')

@section('title', isset($job) ? 'Edit Perjalanan' : 'Buat Perjalanan')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="d-flex align-items-center mb-4">
            <a href="{{ route('fleet.jobs.index') }}" class="btn btn-light border btn-sm me-3">
                <i class="ph ph-arrow-left"></i>
            </a>
            <h4 class="fw-bold mb-0 text-dark">{{ isset($job) ? 'Edit Data Perjalanan' : 'Buat Perjalanan Baru' }}</h4>
        </div>

        <div class="card">
            <div class="card-body p-4">
                @php
                    $defaultDest = old('destination', isset($job) ? $job->destination : ($prefillOrder ? "Order {$prefillOrder->order_code} - {$prefillOrder->customer_name} ({$prefillOrder->address})" : ''));
                    $defaultNotes = old('notes', isset($job) ? $job->notes : ($prefillOrder ? "Pengiriman pesanan online {$prefillOrder->order_code} | WA: {$prefillOrder->phone}" : ''));
                    $defaultDep = old('departure_date', isset($job) && $job->departure_date ? $job->departure_date->format('Y-m-d\TH:i') : date('Y-m-d\TH:i'));
                    $defaultRev = old('revenue', isset($job) ? (int) $job->revenue : ($prefillOrder ? (int) $prefillOrder->shipping_cost : 0));
                    $defaultOps = old('operational_cost', isset($job) ? (int) $job->operational_cost : 0);
                @endphp

                <form action="{{ isset($job) ? route('fleet.jobs.update', $job->id) : route('fleet.jobs.store') }}" method="POST">
                    @csrf
                    @if(isset($job))
                        @method('PUT')
                    @endif

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Pilih Kendaraan</label>
                            <select class="form-select" name="vehicle_id" required>
                                <option value="">-- Pilih Kendaraan --</option>
                                @foreach($vehicles as $vehicle)
                                    <option value="{{ $vehicle->id }}" {{ old('vehicle_id', $job->vehicle_id ?? '') == $vehicle->id ? 'selected' : '' }}>{{ $vehicle->plate_number }} - {{ $vehicle->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Pilih Driver</label>
                            <select class="form-select" name="driver_id" required>
                                <option value="">-- Pilih Driver --</option>
                                @foreach($drivers as $driver)
                                    <option value="{{ $driver->id }}" {{ old('driver_id', $job->driver_id ?? '') == $driver->id ? 'selected' : '' }}>{{ $driver->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="mb-4 p-3 rounded-3 border bg-light">
                        <div class="row g-2 align-items-end">
                            <div class="col-md-8">
                                <label class="form-label fw-medium mb-1">Pilih Tarif Rute Tersimpan (Opsional)</label>
                                <select id="ratePresetSelect" class="form-select form-select-sm" onchange="applyFleetRate()">
                                    <option value="">-- Isi manual tujuan &amp; ongkos di bawah --</option>
                                    @foreach($rates ?? [] as $rate)
                                        <option value="{{ $rate->origin }} -> {{ $rate->destination }}"
                                                data-r4="{{ (int) $rate->rate_4_m3 }}"
                                                data-r6="{{ (int) $rate->rate_6_m3 }}">
                                            {{ $rate->origin }} &rarr; {{ $rate->destination }} (4m³: Rp {{ number_format($rate->rate_4_m3, 0, ',', '.') }} | 6m³: Rp {{ number_format($rate->rate_6_m3, 0, ',', '.') }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-medium mb-1">Kapasitas Angkutan</label>
                                <select id="rateVolumeSelect" class="form-select form-select-sm" onchange="applyFleetRate()">
                                    <option value="4">4 Kubik (m³)</option>
                                    <option value="6">6 Kubik (m³)</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-8">
                            <label class="form-label fw-medium">Tujuan / Proyek</label>
                            <input type="text" id="fleetDestinationInput" class="form-control" name="destination" value="{{ $defaultDest }}" required placeholder="Contoh: Kirim Semen ke Proyek Pak Budi">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-medium">Waktu Berangkat</label>
                            <input type="datetime-local" class="form-control" name="departure_date" value="{{ $defaultDep }}" required>
                        </div>
                    </div>

                    <div class="p-3 rounded-3 mb-4 border" style="background-color: var(--sb-surface-muted);">
                        <h6 class="fw-bold mb-3"><i class="ph ph-check-square-offset text-accent me-2"></i> Ceklis Kendaraan Sebelum Jalan</h6>
                        <div class="row">
                            <div class="col-6 col-md-3 mb-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="cek_ban" id="cek_ban" checked>
                                    <label class="form-check-label" for="cek_ban">Ban &amp; Roda</label>
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
                            <input type="number" id="fleetRevenueInput" class="form-control tabular-nums" name="revenue" min="0" value="{{ $defaultRev }}" placeholder="Isi 0 jika armada internal">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Biaya Operasional (BBM/Tol)</label>
                            <input type="number" class="form-control tabular-nums" name="operational_cost" min="0" value="{{ $defaultOps }}">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-medium">Catatan Tambahan</label>
                        <textarea class="form-control" name="notes" rows="2" placeholder="Opsional...">{{ $defaultNotes }}</textarea>
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
<script>
function applyFleetRate() {
    const sel = document.getElementById('ratePresetSelect');
    const vol = document.getElementById('rateVolumeSelect').value;
    const opt = sel.options[sel.selectedIndex];
    if (!opt || !opt.value) return;
    document.getElementById('fleetDestinationInput').value = opt.value;
    const rate = vol === '6' ? opt.dataset.r6 : opt.dataset.r4;
    if (rate && Number(rate) > 0) {
        document.getElementById('fleetRevenueInput').value = rate;
    }
}
</script>
@endsection
