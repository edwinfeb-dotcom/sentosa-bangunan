<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Imports\EmployeesImport;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class EmployeeController extends Controller
{
    public function index()
    {
        // Tampilkan semua user kecuali owner, urutkan dari yang terbaru
        $employees = User::where('role', '!=', 'owner')->latest()->paginate(10);
        return view('hr.employees.index', compact('employees'));
    }

    public function create()
    {
        return view('hr.employees.form');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'role' => 'required|in:admin,kasir,gudang,driver,karyawan',
            'photo' => 'nullable|image|max:2048',
            'phone' => 'nullable|string|max:20',
            'join_date' => 'required|date',
        ]);

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('employees', 'public');
        }

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'employee_discount' => $request->employee_discount ?? 0,
            'daily_salary' => $request->daily_salary ?? 100000,
            'monthly_salary' => $request->monthly_salary ?? 0,
            'absence_deduction_per_day' => $request->absence_deduction_per_day ?? 0,
            'photo' => $photoPath,
            'birth_date' => $request->birth_date,
            'birth_place' => $request->birth_place,
            'origin' => $request->origin,
            'address' => $request->address,
            'phone' => $request->phone,
            'join_date' => $request->join_date,
            'employee_status' => $request->employee_status ?? 'aktif',
            'employee_category' => $request->employee_category ?? 'karyawan tetap',
        ]);

        return redirect()->route('hr.employees.index')->with('success', 'Data karyawan berhasil ditambahkan.');
    }

    public function edit(User $employee)
    {
        return view('hr.employees.form', compact('employee'));
    }

    public function update(Request $request, User $employee)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $employee->id,
            'role' => 'required|in:admin,kasir,gudang,driver,karyawan',
            'photo' => 'nullable|image|max:2048',
        ]);

        $data = $request->except(['password', 'photo']);
        $data['monthly_salary'] = $request->filled('monthly_salary') ? $request->monthly_salary : 0;
        $data['absence_deduction_per_day'] = $request->filled('absence_deduction_per_day') ? $request->absence_deduction_per_day : 0;
        
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        if ($request->hasFile('photo')) {
            // Hapus foto lama jika ada
            if ($employee->photo && Storage::disk('public')->exists($employee->photo)) {
                Storage::disk('public')->delete($employee->photo);
            }
            $data['photo'] = $request->file('photo')->store('employees', 'public');
        }

        $employee->update($data);

        return redirect()->route('hr.employees.index')->with('success', 'Data karyawan berhasil diperbarui.');
    }

    public function destroy(User $employee)
    {
        if ($employee->photo && Storage::disk('public')->exists($employee->photo)) {
            Storage::disk('public')->delete($employee->photo);
        }
        $employee->delete();
        return redirect()->route('hr.employees.index')->with('success', 'Data karyawan berhasil dihapus.');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,xlsm,csv,txt|max:25600',
        ]);

        try {
            @set_time_limit(180);
            $importer = new EmployeesImport();
            $ext = strtolower($request->file('file')->getClientOriginalExtension());

            DB::transaction(function () use ($importer, $request, $ext) {
                $importer->import($request->file('file')->getRealPath(), $ext);
            });

            if ($importer->createdCount === 0 && $importer->updatedCount === 0 && $importer->debtsImported === 0) {
                return back()->with('error', 'Tidak ada data karyawan atau kasbon aktif yang terbaca di file ini.');
            }

            return back()->with('success', "Import karyawan selesai: {$importer->createdCount} baru, {$importer->updatedCount} diperbarui, {$importer->debtsImported} kasbon aktif tercatat.");
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal mengimport Excel karyawan: ' . $e->getMessage());
        }
    }
}