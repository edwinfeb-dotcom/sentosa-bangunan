<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\PublicOrder;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CustomerAuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return Auth::user()->role === 'pelanggan'
                ? redirect()->route('catalog.account')
                : redirect()->route('dashboard');
        }

        return view('catalog.auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            if (Auth::user()->role !== 'pelanggan') {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()->withErrors([
                    'email' => 'Akun staff/internal tidak dapat masuk dari halaman pembeli.',
                ])->onlyInput('email');
            }

            $request->session()->regenerate();
            return redirect()->intended(route('catalog.account'));
        }

        return back()->withErrors([
            'email' => 'Email atau password pembeli salah.',
        ])->onlyInput('email');
    }

    public function showRegister()
    {
        if (Auth::check()) {
            return redirect()->route('catalog.account');
        }
        return view('catalog.auth.register');
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string|max:20',
            'address' => 'nullable|string|max:500',
            'password' => 'required|string|min:6|confirmed',
        ], [
            'email.unique' => 'Email sudah terdaftar. Silakan login.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ]);

        $user = DB::transaction(function () use ($request) {
            $customer = Customer::create([
                'name' => $request->name,
                'type' => 'reguler',
                'phone' => $request->phone,
                'address' => $request->address,
                'is_active' => true,
            ]);

            return User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => 'pelanggan',
                'customer_id' => $customer->id,
                'phone' => $request->phone,
                'address' => $request->address,
            ]);
        });

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('catalog.account')->with('success', 'Selamat datang! Akun pelanggan Anda berhasil dibuat.');
    }

    public function account()
    {
        $user = auth()->user();
        $customer = $user->customer;

        $orders = PublicOrder::with('items.product')
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id);
                if ($user->phone) {
                    $q->orWhere('phone', $user->phone);
                }
            })
            ->latest()
            ->paginate(10);

        return view('catalog.account', compact('user', 'customer', 'orders'));
    }

    public function updateProfile(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'address' => 'nullable|string|max:500',
        ]);

        $user = auth()->user();
        $user->update([
            'name' => $request->name,
            'phone' => $request->phone,
            'address' => $request->address,
        ]);

        if ($user->customer) {
            $user->customer->update([
                'name' => $request->name,
                'phone' => $request->phone,
                'address' => $request->address,
            ]);
        }

        return back()->with('success', 'Profil berhasil diperbarui.');
    }
}
