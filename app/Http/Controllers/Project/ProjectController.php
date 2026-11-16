<?php

namespace App\Http\Controllers\Project;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Customer;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function index()
    {
        $projects = Project::with('customer')->latest()->paginate(10);
        return view('project.projects.index', compact('projects'));
    }

    public function create()
    {
        $customers = Customer::where('type', 'kontraktor')->where('is_active', true)->get(); // Hanya kontraktor
        return view('project.projects.form', compact('customers'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'name' => 'required|string|max:255',
            'budget' => 'required|numeric|min:0',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'required|in:active,completed,cancelled',
        ]);

        Project::create($request->all());

        return redirect()->route('project.projects.index')->with('success', 'Proyek berhasil ditambahkan.');
    }

    public function edit(Project $project)
    {
        $customers = Customer::where('type', 'kontraktor')->where('is_active', true)->get();
        return view('project.projects.form', compact('project', 'customers'));
    }

    public function update(Request $request, Project $project)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'name' => 'required|string|max:255',
            'budget' => 'required|numeric|min:0',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'required|in:active,completed,cancelled',
        ]);

        $project->update($request->all());

        return redirect()->route('project.projects.index')->with('success', 'Proyek berhasil diperbarui.');
    }

    public function destroy(Project $project)
    {
        $project->delete();
        return redirect()->route('project.projects.index')->with('success', 'Proyek berhasil dihapus.');
    }
}