<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreApplicationRequest;
use App\Http\Resources\ApplicationResource;
use App\Models\Application;
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
    public function index(Request $request)
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

        $validStatuses  = ['wishlist', 'applied', 'phone_screen', 'interview', 'offer', 'rejected', 'archived'];
        $validPriorities = ['low', 'medium', 'high'];

        if ($request->filled('status') && in_array($request->status, $validStatuses)) {
            $query->where('status', $request->status);
        } else {
            if (!$request->boolean('show_rejected', false)) {
                $query->where('status', '!=', 'rejected');
            }
            if (!$request->boolean('show_archived', false)) {
                $query->where('status', '!=', 'archived');
            }
        }

        if ($request->filled('priority') && in_array($request->priority, $validPriorities)) {
            $query->where('priority', $request->priority);
        }

        return ApplicationResource::collection($query->latest()->paginate($perPage));
    }

    public function store(StoreApplicationRequest $request)
    {
        $app = $request->user()->applications()->create($request->validated());
        return (new ApplicationResource($app->load(['contacts', 'interviewRounds'])))->response()->setStatusCode(201);
    }

    public function show(string $hash)
    {
        $application = $this->findByHash($hash);
        $this->authorize('view', $application);
        return new ApplicationResource($application->load(['contacts', 'interviewRounds']));
    }

    public function update(StoreApplicationRequest $request, string $hash)
    {
        $application = $this->findByHash($hash);
        $this->authorize('update', $application);
        $data = $request->validated();
        if (isset($data['status']) && $data['status'] === 'archived' && !$application->archived_at) {
            $data['archived_at'] = now();
        }
        $application->update($data);
        return new ApplicationResource($application->fresh(['contacts', 'interviewRounds']));
    }

    public function destroy(string $hash)
    {
        $application = $this->findByHash($hash);
        $this->authorize('delete', $application);
        $application->delete();
        return response()->json(null, 204);
    }
}
