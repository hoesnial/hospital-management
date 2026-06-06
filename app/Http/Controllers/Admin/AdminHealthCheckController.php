<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreHealthCheckRequest;
use App\Http\Requests\Admin\UpdateHealthCheckRequest;
use App\Models\HealthCheck;
use Inertia\Inertia;

class AdminHealthCheckController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $healthChecks = HealthCheck::all();
        return Inertia::render('Admin/HealthChecks/Index', [
            'healthChecks' => $healthChecks,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Admin/HealthChecks/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreHealthCheckRequest $request)
    {
        HealthCheck::create($request->validated());

        return redirect()->route('admin.health-checks.index')->with('success', 'Health check created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $health_check)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $health_check)
    {
        $healthCheck = HealthCheck::findOrFail($health_check);
        return Inertia::render('Admin/HealthChecks/Edit', [
            'healthCheck' => $healthCheck,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateHealthCheckRequest $request, string $health_check)
    {
        $healthCheck = HealthCheck::findOrFail($health_check);
        $healthCheck->update($request->validated());

        return redirect()->route('admin.health-checks.index')->with('success', 'Health check updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $health_check)
    {
        $healthCheck = HealthCheck::findOrFail($health_check);
        $healthCheck->delete();

        if (request()->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Health check deleted successfully.']);
        }

        return redirect()->route('admin.health-checks.index')->with('success', 'Health check deleted successfully.');
    }
}
