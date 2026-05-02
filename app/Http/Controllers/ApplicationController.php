<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreApplicationRequest;
use App\Models\Application;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApplicationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = $request->user()
            ->applications()
            ->withCount('interviewRounds');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(fn($q) =>
                $q->where('company', 'like', "%{$search}%")
                  ->orWhere('role', 'like', "%{$search}%")
            );
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('priority') && $request->priority !== 'all') {
            $query->where('priority', $request->priority);
        }

        return response()->json($query->latest()->get());
    }

    public function store(StoreApplicationRequest $request): JsonResponse
    {
        $app = $request->user()->applications()->create($request->validated());
        return response()->json($app->load(['contacts', 'interviewRounds']), 201);
    }

    public function show(Application $application): JsonResponse
    {
        $this->authorize('view', $application);
        return response()->json($application->load(['contacts', 'interviewRounds']));
    }

    public function update(StoreApplicationRequest $request, Application $application): JsonResponse
    {
        $this->authorize('update', $application);
        $application->update($request->validated());
        return response()->json($application->fresh(['contacts', 'interviewRounds']));
    }

    public function destroy(Application $application): JsonResponse
    {
        $this->authorize('delete', $application);
        $application->delete();
        return response()->json(null, 204);
    }
}
