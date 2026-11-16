<?php

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use Illuminate\Http\Request;

class VehicleController extends Controller
{
    public function index()
    {
        $vehicles = Vehicle::latest()->paginate(10);
        return view('fleet.vehicles.index', compact('vehicles'));
    }

    public function create()
    {
        return view('fleet.vehicles.form');
    }

    public function store(Request $request)
    {
        $request->validate([
            'plate_number' => 'required|string|max:20|unique:vehicles,plate_number',
            'name' => 'required|string|max:255',
            'type' => 'required|in:internal,komersial,vendor',
        ]);

        Vehicle::create([
            'plate_number' => strtoupper($request->plate_number),
            'name' => $request->name,
            'type' => $request->type,
            'is_active' => $request->has('is_active') ? true : false,
        ]);

        return redirect()->route('fleet.vehicles.index')->with('success', 'Kendaraan berhasil ditambahkan.');
    }

    public function edit(Vehicle $vehicle)
    {
        return view('fleet.vehicles.form', compact('vehicle'));
    }

    public function update(Request $request, Vehicle $vehicle)
    {
        $request->validate([
            'plate_number' => 'required|string|max:20|unique:vehicles,plate_number,' . $vehicle->id,
            'name' => 'required|string|max:255',
            'type' => 'required|in:internal,komersial,vendor',
        ]);

        $vehicle->update([
            'plate_number' => strtoupper($request->plate_number),
            'name' => $request->name,
            'type' => $request->type,
            'is_active' => $request->has('is_active') ? true : false,
        ]);

        return redirect()->route('fleet.vehicles.index')->with('success', 'Kendaraan berhasil diperbarui.');
    }

    public function destroy(Vehicle $vehicle)
    {
        $vehicle->delete();
        return redirect()->route('fleet.vehicles.index')->with('success', 'Kendaraan berhasil dihapus.');
    }
}