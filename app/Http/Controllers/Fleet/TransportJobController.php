<?php

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Models\PublicOrder;
use App\Models\TransportJob;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Http\Request;

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

        return view('fleet.jobs.index', compact('jobs', 'summary', 'vehicles', 'drivers'));
    }

    public function create(Request $request)
    {
        $vehicles = Vehicle::where('is_active', true)->get();
        $drivers = User::where('role', 'driver')->where('employee_status', 'aktif')->get();

        $prefillOrder = null;
        if ($request->filled('order_id')) {
            $prefillOrder = PublicOrder::find($request->order_id);
        }

        return view('fleet.jobs.form', compact('vehicles', 'drivers', 'prefillOrder'));
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
}
