<?php

namespace App\Http\Controllers\Setting;

use App\Http\Controllers\Controller;
use App\Models\StoreSetting;
use App\Models\Wallet;
use Illuminate\Http\Request;

class StoreSettingController extends Controller
{
    public function index()
    {
        $setting = StoreSetting::current();
        $bankWallets = Wallet::whereIn('type', ['bank', 'ewallet'])
            ->orderBy('type')
            ->orderBy('name')
            ->get();

        return view('setting.store.index', compact('setting', 'bankWallets'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'store_name' => 'required|string|max:120',
            'store_address' => 'required|string|max:500',
            'store_phone' => 'nullable|string|max:50',
            'bank_transfer_note' => 'nullable|string|max:255',
        ]);

        $setting = StoreSetting::current();
        $setting->update([
            'store_name' => $request->store_name,
            'store_address' => $request->store_address,
            'store_phone' => $request->store_phone,
            'bank_transfer_note' => $request->bank_transfer_note,
            'show_customer_accounts' => $request->has('show_customer_accounts'),
        ]);

        return back()->with('success', 'Profil toko & pengaturan rekening transfer berhasil diperbarui.');
    }
}
