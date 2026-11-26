@extends('layouts.app')

@section('title', 'Pesanan Online')

@section('content')
<div class="page-header">
    <div>
        <h2 class="page-title">Pesanan Online (Katalog Publik)</h2>
        <p class="page-subtitle">Proses pesanan masuk, potong stok otomatis, dan perbarui status pelacakan pembeli.</p>
    </div>
</div>

<!-- Filter -->
<div class="card mb-4">
    <div class="card-body p-3">
        <form action="{{ route('public-orders.index') }}" method="GET" class="row g-2 align-items-end">
            <div class="col-md-5">
                <label class="form-label">Cari Kode / Nama / No. WA</label>
                <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="PUB-... atau nama pembeli">
            </div>
            <div class="col-md-4">
                <label class="form-label">Status Pesanan</label>
                <select name="status" class="form-select">
                    <option value="">Semua Status</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Menunggu Konfirmasi</option>
                    <option value="packed" {{ request('status') == 'packed' ? 'selected' : '' }}>Sedang Dikemas</option>
                    <option value="shipped" {{ request('status') == 'shipped' ? 'selected' : '' }}>Sedang Dikirim</option>
                    <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Selesai</option>
                    <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Dibatalkan</option>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary w-100">Filter</button>
                <a href="{{ route('public-orders.index') }}" class="btn btn-light border">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Kode Pesanan</th>
                        <th>Pembeli</th>
                        <th>Pengiriman</th>
                        <th>Pembayaran</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                    <tr>
                        <td>
                            <div class="fw-bold text-primary">{{ $order->order_code }}</div>
                            <small class="text-muted">{{ $order->created_at->format('d/m/Y H:i') }}</small>
                        </td>
                        <td>
                            <div class="fw-semibold text-dark">{{ $order->customer_name }}</div>
                            <small class="text-muted">{{ $order->phone }}</small>
                        </td>
                        <td>
                            @if($order->shipping_method === 'antar')
                                <span class="badge bg-primary bg-opacity-10 text-primary">Antar</span>
                                <div class="small text-muted mt-1" style="max-width: 180px;">{{ Str::limit($order->address, 40) }}</div>
                            @else
                                <span class="badge bg-info bg-opacity-10 text-info">Ambil di Toko</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge {{ $order->payment_method === 'cash' ? 'bg-warning bg-opacity-25 text-dark' : 'bg-success bg-opacity-10 text-success' }}">
                                {{ strtoupper($order->payment_method) }}
                            </span>
                        </td>
                        <td class="fw-bold">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</td>
                        <td>
                            <span class="badge {{ $order->status_badge_class }}">{{ $order->status_label }}</span>
                        </td>
                        <td class="text-end">
                            <div class="d-inline-flex gap-1 align-items-center">
                                @if($order->phone)
                                    @php
                                        $waNum = preg_replace('/[^0-9]/', '', $order->phone);
                                        if (str_starts_with($waNum, '0')) { $waNum = '62' . substr($waNum, 1); }
                                        $waMsg = rawurlencode("Halo {$order->customer_name}, pesanan Anda {$order->order_code} (Rp " . number_format($order->total_amount, 0, ',', '.') . ") saat ini berstatus: {$order->status_label}. — Sentosa Bangunan");
                                    @endphp
                                    <a href="https://wa.me/{{ $waNum }}?text={{ $waMsg }}" target="_blank" class="btn btn-sm btn-light border text-success" title="Konfirmasi WA Pembeli">
                                        <i class="ph ph-whatsapp-logo"></i>
                                    </a>
                                @endif
                                <a href="{{ route('public-orders.print', $order->id) }}" class="btn btn-sm btn-light border" title="Cetak Nota Pesanan">
                                    <i class="ph ph-printer"></i>
                                </a>
                                <button type="button" class="btn btn-sm btn-light border" data-bs-toggle="modal" data-bs-target="#orderDetailModal{{ $order->id }}" title="Lihat Item">
                                    <i class="ph ph-eye"></i>
                                </button>

                                @if($order->shipping_method === 'antar' && $order->status !== 'cancelled' && in_array(auth()->user()->role, ['owner', 'admin', 'gudang', 'driver']))
                                    <a href="{{ route('fleet.jobs.create', ['order_id' => $order->id]) }}" class="btn btn-sm btn-outline-dark" title="Tugaskan Armada Pengiriman">
                                        <i class="ph ph-truck"></i>
                                    </a>
                                @endif

                                @if($order->status === 'pending')
                                    <form action="{{ route('public-orders.status', $order->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Kemas pesanan ini? Stok dan Buku Kas akan otomatis diperbarui.')">
                                        @csrf
                                        <input type="hidden" name="status" value="packed">
                                        <button type="submit" class="btn btn-sm btn-primary">Kemas</button>
                                    </form>
                                @elseif($order->status === 'packed')
                                    <form action="{{ route('public-orders.status', $order->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        <input type="hidden" name="status" value="shipped">
                                        <button type="submit" class="btn btn-sm btn-info text-white">Kirim</button>
                                    </form>
                                @elseif($order->status === 'shipped')
                                    <form action="{{ route('public-orders.status', $order->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        <input type="hidden" name="status" value="completed">
                                        <button type="submit" class="btn btn-sm btn-success">Selesai</button>
                                    </form>
                                @endif

                                @if(in_array($order->status, ['pending', 'packed', 'shipped']))
                                    <form action="{{ route('public-orders.status', $order->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Batalkan pesanan ini? Stok & kas akan otomatis dikembalikan jika sudah terpotong.')">
                                        @csrf
                                        <input type="hidden" name="status" value="cancelled">
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Batalkan Pesanan">
                                            <i class="ph ph-x"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="ph ph-globe fs-1 d-block mb-2 opacity-50"></i>
                            Belum ada pesanan online.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($orders->hasPages())
        <div class="card-footer bg-white">{{ $orders->links() }}</div>
    @endif
</div>

<!-- Detail Modals -->
@foreach($orders as $order)
<div class="modal fade" id="orderDetailModal{{ $order->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Rincian {{ $order->order_code }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3 small">
                    <div><strong>Pembeli:</strong> {{ $order->customer_name }} ({{ $order->phone }})</div>
                    <div><strong>Alamat:</strong> {{ $order->address ?? '-' }}</div>
                    @if($order->notes)
                        <div><strong>Catatan:</strong> {{ $order->notes }}</div>
                    @endif
                </div>
                <hr>
                @foreach($order->items as $item)
                    <div class="d-flex justify-content-between small mb-2">
                        <span>{{ $item->qty }}x {{ $item->product->name ?? 'Produk' }}</span>
                        <span class="fw-semibold">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</span>
                    </div>
                @endforeach
                @if($order->shipping_cost > 0)
                    <div class="d-flex justify-content-between small text-muted mb-2">
                        <span>Ongkos Kirim</span>
                        <span>Rp {{ number_format($order->shipping_cost, 0, ',', '.') }}</span>
                    </div>
                @endif
                <div class="d-flex justify-content-between border-top pt-2 fw-bold">
                    <span>Total</span>
                    <span class="text-primary">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</span>
                </div>
            </div>
            @if(!in_array($order->status, ['completed', 'cancelled']))
            <div class="modal-footer justify-content-between">
                <form action="{{ route('public-orders.status', $order->id) }}" method="POST" onsubmit="return confirm('Batalkan pesanan ini?')">
                    @csrf
                    <input type="hidden" name="status" value="cancelled">
                    <button type="submit" class="btn btn-sm btn-outline-danger">Batalkan Pesanan</button>
                </form>
                <button type="button" class="btn btn-sm btn-light border" data-bs-dismiss="modal">Tutup</button>
            </div>
            @endif
        </div>
    </div>
</div>
@endforeach
@endsection
