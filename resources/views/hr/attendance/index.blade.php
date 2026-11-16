@extends('layouts.app')

@section('title', 'Absensi Karyawan')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1" style="color: #1E293B;">Absensi Karyawan</h4>
        <p class="text-muted mb-0" style="font-size: 0.875rem;">Catat kehadiran dan hitung HOK secara otomatis.</p>
    </div>
</div>

<div class="row g-4">
    <!-- Kartu Clock In/Out -->
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-4 text-center d-flex flex-column align-items-center justify-content-center">
                <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                    <i class="ph ph-fingerprint fs-1"></i>
                </div>
                <h5 class="fw-bold mb-1" style="color: #1E293B;">Absensi Hari Ini</h5>
                <p class="text-muted mb-4">{{ \Carbon\Carbon::now('Asia/Pontianak')->translatedFormat('l, d F Y') }}</p>
                <div class="fs-2 fw-bold text-primary mb-4 font-monospace" id="jam-sekarang">
                    {{ \Carbon\Carbon::now('Asia/Pontianak')->format('H:i:s') }}
                </div>

                @if(session('success'))
                    <div class="alert alert-success w-100 py-2" style="font-size: 0.85rem;">{{ session('success') }}</div>
                @endif
                @if(session('error'))
                    <div class="alert alert-danger w-100 py-2" style="font-size: 0.85rem;">{{ session('error') }}</div>
                @endif

                @if(!$attendanceToday)
                    <form action="{{ route('hr.attendance.clock-in') }}" method="POST" class="w-100">
                        @csrf
                        <button type="submit" class="btn btn-primary btn-lg w-100 d-flex justify-content-center align-items-center gap-2">
                            <i class="ph ph-sign-in fs-5"></i> Clock In (Masuk)
                        </button>
                    </form>
                @elseif(!$attendanceToday->clock_out)
                    <div class="bg-light rounded p-2 w-100 mb-3 d-flex justify-content-between px-4">
                        <span class="text-muted">Jam Masuk:</span>
                        <span class="fw-bold text-success">{{ $attendanceToday->clock_in }}</span>
                    </div>
                    <form action="{{ route('hr.attendance.clock-out') }}" method="POST" class="w-100">
                        @csrf
                        <button type="submit" class="btn btn-danger btn-lg w-100 d-flex justify-content-center align-items-center gap-2">
                            <i class="ph ph-sign-out fs-5"></i> Clock Out (Pulang)
                        </button>
                    </form>
                @else
                    <div class="bg-success bg-opacity-10 rounded p-3 w-100 text-success">
                        <h6 class="fw-bold mb-2">Selesai Bekerja!</h6>
                        <div class="d-flex justify-content-between small">
                            <span>Masuk: {{ $attendanceToday->clock_in }}</span>
                            <span>Pulang: {{ $attendanceToday->clock_out }}</span>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Tabel Riwayat -->
    <div class="col-md-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom pt-3 pb-3">
                <h6 class="fw-bold mb-0" style="color: #1E293B;"><i class="ph ph-clock-counter-clockwise me-2"></i> Riwayat Absensi Anda</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Masuk</th>
                                <th>Pulang</th>
                                <th>HOK</th>
                                <th>Lembur</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($history as $row)
                            <tr>
                                <td class="fw-medium" style="color: #1E293B;">{{ $row->date->format('d/m/Y') }}</td>
                                <td><span class="badge bg-success bg-opacity-10 text-success">{{ $row->clock_in ?? '-' }}</span></td>
                                <td><span class="badge bg-danger bg-opacity-10 text-danger">{{ $row->clock_out ?? '-' }}</span></td>
                                <td><span class="badge bg-primary bg-opacity-10 text-primary">{{ $row->hok }} HOK</span></td>
                                <td>
                                    @if($row->overtime_hours > 0)
                                        <span class="badge bg-warning bg-opacity-10 text-warning">{{ $row->overtime_hours }} Jam</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <i class="ph ph-calendar-x fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                    Belum ada data absensi.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Script jam berjalan real-time
    setInterval(function() {
        let now = new Date();
        let hours = String(now.getHours()).padStart(2, '0');
        let minutes = String(now.getMinutes()).padStart(2, '0');
        let seconds = String(now.getSeconds()).padStart(2, '0');
        document.getElementById('jam-sekarang').innerText = hours + ':' + minutes + ':' + seconds;
    }, 1000);
</script>
@endpush