<?php

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Models\TransportJob;
use App\Models\Vehicle;
use App\Models\User;
use Illuminate\Http\Request;

class TransportJobController extends Controller
{
    public function index()
    {
        $jobs = TransportJob::with(['vehicle', 'driver'])->latest()->paginate(10);
        return view('fleet.jobs.index', compact('jobs'));
    }

    public function create()
    {
        // Hanya bisa pilih kendaraan yang aktif
        $vehicles = Vehicle::where('is_active', true)->get();
        
        // PERBAIKAN: Gunakan employee_status = 'aktif' untuk user
        $drivers = User::where('role', 'driver')->where('employee_status', 'aktif')->get();
        
        return view('fleet.jobs.form', compact('vehicles', 'drivers'));
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

        // Hitung komisi driver (Misal 10% dari pendapatan)
        $revenue = $request->revenue ?? 0;
        $commission = $revenue * 0.10; 

        // Format checklist dari form
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
        // Fitur saat driver sudah kembali ke toko
        $job->update([
            'status' => 'completed',
            'return_date' => now(),
        ]);

        return back()->with('success', 'Perjalanan selesai. Komisi driver telah dikunci.');
    }
}