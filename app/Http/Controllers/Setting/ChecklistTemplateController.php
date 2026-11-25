<?php

namespace App\Http\Controllers\Setting;

use App\Http\Controllers\Controller;
use App\Models\ChecklistTemplate;
use Illuminate\Http\Request;

class ChecklistTemplateController extends Controller
{
    public function index()
    {
        $sections = ChecklistTemplate::sections();
        $templates = ChecklistTemplate::orderBy('section')->orderBy('sort_order')->get()->groupBy('section');

        return view('setting.checklists.index', compact('sections', 'templates'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'section' => 'required|in:piket,cek_aset,ceklis_pagi,ceklis_sore',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:255',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        ChecklistTemplate::create([
            'section' => $request->section,
            'title' => $request->title,
            'description' => $request->description,
            'sort_order' => $request->sort_order ?? 1,
            'is_active' => true,
        ]);

        return back()->with('success', 'Item ceklis / job desk berhasil ditambahkan.');
    }

    public function update(Request $request, ChecklistTemplate $checklist)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:255',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $checklist->update([
            'title' => $request->title,
            'description' => $request->description,
            'sort_order' => $request->sort_order ?? 1,
            'is_active' => $request->has('is_active'),
        ]);

        return back()->with('success', 'Item ceklis berhasil diperbarui.');
    }

    public function destroy(ChecklistTemplate $checklist)
    {
        $checklist->delete();
        return back()->with('success', 'Item ceklis berhasil dihapus.');
    }
}
