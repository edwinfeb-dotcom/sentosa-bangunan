<?php

namespace App\Http\Controllers\Setting;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\SalesTarget;
use Illuminate\Http\Request;

class SalesTargetController extends Controller
{
    public function index()
    {
        $targets = SalesTarget::orderByDesc('year')->orderByDesc('month')->get()->map(function ($target) {
            $actual = (float) Sale::where('status', 'completed')
                ->whereMonth('created_at', $target->month)
                ->whereYear('created_at', $target->year)
                ->sum('total_amount');

            $target->actual_amount = $actual;
            $target->percentage = $target->target_amount > 0
                ? min(100, round(($actual / $target->target_amount) * 100, 1))
                : 0;
            return $target;
        });

        return view('setting.targets.index', compact('targets'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2020|max:2100',
            'target_amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:255',
        ]);

        SalesTarget::updateOrCreate(
            ['month' => $request->month, 'year' => $request->year],
            ['target_amount' => $request->target_amount, 'notes' => $request->notes]
        );

        return back()->with('success', 'Target penjualan bulanan berhasil disimpan.');
    }

    public function destroy(SalesTarget $target)
    {
        $target->delete();
        return back()->with('success', 'Target penjualan berhasil dihapus.');
    }
}
