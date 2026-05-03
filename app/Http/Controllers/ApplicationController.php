<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreApplicationRequest;
use App\Models\Application;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Vinkla\Hashids\Facades\Hashids;

class ApplicationController extends Controller
{
    private function findByHash(string $hash): Application
    {
        $decoded = Hashids::decode($hash);
        abort_if(empty($decoded), 404);
        return Application::findOrFail($decoded[0]);
    }
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'per_page' => 'nullable|integer|min:1|max:200',
            'page' => 'nullable|integer|min:1',
            'search' => 'nullable|string|max:255',
            'status' => 'nullable|string',
            'priority' => 'nullable|string',
            'show_rejected' => 'nullable|boolean',
            'show_archived' => 'nullable|boolean',
        ]);

        $perPage = $request->input('per_page', 20);
        
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
        } else {
            // Default filters if no specific status is requested
            if (!$request->boolean('show_rejected', false)) {
                $query->where('status', '!=', 'rejected');
            }
            if (!$request->boolean('show_archived', false)) {
                $query->where('status', '!=', 'archived');
            }
        }

        if ($request->filled('priority') && $request->priority !== 'all') {
            $query->where('priority', $request->priority);
        }

        return response()->json($query->latest()->paginate($perPage));
    }

    public function store(StoreApplicationRequest $request): JsonResponse
    {
        $app = $request->user()->applications()->create($request->validated());
        return response()->json($app->load(['contacts', 'interviewRounds']), 201);
    }

    public function show(string $hash): JsonResponse
    {
        $application = $this->findByHash($hash);
        $this->authorize('view', $application);
        return response()->json($application->load(['contacts', 'interviewRounds']));
    }

    public function update(StoreApplicationRequest $request, string $hash): JsonResponse
    {
        $application = $this->findByHash($hash);
        $this->authorize('update', $application);
        $application->update($request->validated());
        return response()->json($application->fresh(['contacts', 'interviewRounds']));
    }

    public function destroy(string $hash): JsonResponse
    {
        $application = $this->findByHash($hash);
        $this->authorize('delete', $application);
        $application->delete();
        return response()->json(null, 204);
    }
}
