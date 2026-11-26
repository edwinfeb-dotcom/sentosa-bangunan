<?php

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Imports\FleetImport;
use App\Models\PublicOrder;
use App\Models\TransportJob;
use App\Models\TransportRate;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransportJobController extends Controller
{
    public function index(Request $request)
    {
        $query = TransportJob::with(['vehicle', 'driver'])->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('vehicle_id')) {
            $query->where('vehicle_id', $request->vehicle_id);
        }
        if ($request->filled('driver_id')) {
            $query->where('driver_id', $request->driver_id);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('departure_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('departure_date', '<=', $request->date_to);
        }

        $summaryQuery = clone $query;
        $totalRevenue = (float) $summaryQuery->sum('revenue');
        $totalOperational = (float) (clone $query)->sum('operational_cost');
        $totalCommission = (float) (clone $query)->sum('driver_commission');
        $activeTrips = (int) (clone $query)->where('status', 'on_trip')->count();

        $summary = [
            'active_trips' => $activeTrips,
            'total_revenue' => $totalRevenue,
            'total_operational' => $totalOperational,
            'total_commission' => $totalCommission,
            'net_profit' => $totalRevenue - $totalOperational - $totalCommission,
        ];

        $jobs = $query->paginate(10)->withQueryString();
        $vehicles = Vehicle::orderBy('plate_number')->get();
        $drivers = User::where('role', 'driver')->orderBy('name')->get();
        $rates = TransportRate::where('is_active', true)->orderBy('origin')->orderBy('destination')->get();

        return view('fleet.jobs.index', compact('jobs', 'summary', 'vehicles', 'drivers', 'rates'));
    }

    public function create(Request $request)
    {
        $vehicles = Vehicle::where('is_active', true)->get();
        $drivers = User::where('role', 'driver')->where('employee_status', 'aktif')->get();
        $rates = TransportRate::where('is_active', true)->orderBy('origin')->orderBy('destination')->get();

        $prefillOrder = null;
        if ($request->filled('order_id')) {
            $prefillOrder = PublicOrder::find($request->order_id);
        }

        return view('fleet.jobs.form', compact('vehicles', 'drivers', 'prefillOrder', 'rates'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'driver_id' => 'required|exists:users,id',
            'destination' => 'required|string|max:255',
            'departure_date' => 'required|date',
            'revenue' => 'nullable|numeric|min:0',
            'operational_cost' => 'nullable|numeric|min:0',
        ]);

        $revenue = $request->revenue ?? 0;
        $commission = $revenue * 0.10;

        $checklist = [
            'ban' => $request->has('cek_ban'),
            'dongkrak' => $request->has('cek_dongkrak'),
            'apar' => $request->has('cek_apar'),
            'lampu' => $request->has('cek_lampu'),
        ];

        TransportJob::create([
            'vehicle_id' => $request->vehicle_id,
            'driver_id' => $request->driver_id,
            'destination' => $request->destination,
            'departure_date' => $request->departure_date,
            'status' => 'on_trip',
            'revenue' => $revenue,
            'operational_cost' => $request->operational_cost ?? 0,
            'driver_commission' => $commission,
            'checklist' => $checklist,
            'notes' => $request->notes,
        ]);

        return redirect()->route('fleet.jobs.index')->with('success', 'Perjalanan armada berhasil dibuat.');
    }

    public function complete(TransportJob $job)
    {
        $job->update([
            'status' => 'completed',
            'return_date' => now(),
        ]);

        return back()->with('success', 'Perjalanan selesai. Komisi driver telah dikunci.');
    }

    public function waybill(TransportJob $job)
    {
        $job->load(['vehicle', 'driver']);
        return view('fleet.jobs.waybill', compact('job'));
    }

    public function edit(TransportJob $job)
    {
        $vehicles = Vehicle::where('is_active', true)->get();
        $drivers = User::where('role', 'driver')->where('employee_status', 'aktif')->get();
        $rates = TransportRate::where('is_active', true)->orderBy('origin')->orderBy('destination')->get();
        $prefillOrder = null;

        return view('fleet.jobs.form', compact('job', 'vehicles', 'drivers', 'rates', 'prefillOrder'));
    }

    public function update(Request $request, TransportJob $job)
    {
        $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'driver_id' => 'required|exists:users,id',
            'destination' => 'required|string|max:255',
            'departure_date' => 'required|date',
            'revenue' => 'nullable|numeric|min:0',
            'operational_cost' => 'nullable|numeric|min:0',
        ]);

        $revenue = $request->revenue ?? 0;

        $job->update([
            'vehicle_id' => $request->vehicle_id,
            'driver_id' => $request->driver_id,
            'destination' => $request->destination,
            'departure_date' => $request->departure_date,
            'revenue' => $revenue,
            'operational_cost' => $request->operational_cost ?? 0,
            'driver_commission' => $revenue * 0.10,
            'notes' => $request->notes,
        ]);

        return redirect()->route('fleet.jobs.index')->with('success', 'Data perjalanan armada berhasil diperbarui.');
    }

    public function destroy(TransportJob $job)
    {
        $job->delete();
        return redirect()->route('fleet.jobs.index')->with('success', 'Data perjalanan armada berhasil dihapus.');
    }

    public function storeRate(Request $request)
    {
        $request->validate([
            'origin' => 'required|string|max:120',
            'destination' => 'required|string|max:160',
            'rate_4_m3' => 'required|numeric|min:0',
            'rate_6_m3' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:255',
        ]);

        TransportRate::updateOrCreate(
            ['origin' => trim($request->origin), 'destination' => trim($request->destination)],
            [
                'rate_4_m3' => $request->rate_4_m3,
                'rate_6_m3' => $request->rate_6_m3,
                'notes' => $request->notes,
                'is_active' => true,
            ]
        );

        return back()->with('success', 'Tarif rute angkutan berhasil disimpan.');
    }

    public function destroyRate(TransportRate $rate)
    {
        $rate->delete();
        return back()->with('success', 'Tarif rute angkutan dihapus.');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,xlsm,csv,txt|max:25600',
        ]);

        try {
            @set_time_limit(180);
            $importer = new FleetImport();
            $importTrips = $request->boolean('import_trips');
            $ext = strtolower($request->file('file')->getClientOriginalExtension());

            DB::transaction(function () use ($importer, $request, $ext, $importTrips) {
                $importer->import($request->file('file')->getRealPath(), $importTrips, $ext);
            });

            if ($importer->ratesSynced === 0 && $importer->vehiclesSynced === 0 && $importer->jobsCreated === 0) {
                return back()->with('error', 'Tidak ada tarif ongkos atau data armada yang terdeteksi di file Excel.');
            }

            return back()->with('success', "Import angkutan selesai: {$importer->ratesSynced} tarif rute disinkronkan, {$importer->vehiclesSynced} armada baru, {$importer->jobsCreated} histori trip ditambahkan.");
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal mengimport Excel angkutan: ' . $e->getMessage());
        }
    }
}
