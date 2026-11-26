<?php

namespace App\Http\Controllers\Setting;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AccessController extends Controller
{
    public function index()
    {
        $users = User::where('role', '!=', 'pelanggan')->orderBy('role')->paginate(10);
        return view('setting.access.index', compact('users'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'role' => 'required|in:owner,admin,kasir,gudang,driver,karyawan',
            'password' => 'required|string|min:4',
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'role' => $request->role,
            'password' => Hash::make($request->password),
            'employee_status' => 'aktif',
            'daily_salary' => 100000,
        ]);

        return redirect()->route('setting.access.index')->with('success', 'Akun ' . $request->name . ' (' . strtoupper($request->role) . ') berhasil dibuat.');
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'role' => 'required|in:owner,admin,kasir,gudang,driver,karyawan',
            'password' => 'nullable|min:4',
        ]);

        $data = ['role' => $request->role];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return redirect()->route('setting.access.index')->with('success', 'Akses dan sandi untuk ' . $user->name . ' berhasil diperbarui.');
    }

    public function toggleStatus(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Anda tidak dapat menonaktifkan akun Anda sendiri yang sedang login.');
        }

        $next = $user->employee_status === 'nonaktif' ? 'aktif' : 'nonaktif';
        $user->update(['employee_status' => $next]);

        return back()->with('success', 'Status login ' . $user->name . ' diubah menjadi: ' . strtoupper($next) . '.');
    }
}
