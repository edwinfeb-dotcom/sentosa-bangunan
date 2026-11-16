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
        // Tampilkan semua user
        $users = User::orderBy('role')->paginate(10);
        return view('setting.access.index', compact('users'));
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'role' => 'required|in:owner,admin,kasir,gudang,driver,karyawan',
            'password' => 'nullable|min:4', // PIN atau password baru
        ]);

        $data = ['role' => $request->role];

        // Jika password diisi, berarti Owner mau mereset password/PIN user ini
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return redirect()->route('setting.access.index')->with('success', 'Akses dan sandi untuk ' . $user->name . ' berhasil diperbarui.');
    }
}