<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInterviewRoundRequest;
use App\Models\Application;
use App\Models\InterviewRound;
use Illuminate\Http\JsonResponse;
use Vinkla\Hashids\Facades\Hashids;

class InterviewRoundController extends Controller
{
    private function findApp(string $hash): Application
    {
        $decoded = Hashids::decode($hash);
        abort_if(empty($decoded), 404);
        return Application::findOrFail($decoded[0]);
    }

    public function index(string $hash): JsonResponse
    {
        $application = $this->findApp($hash);
        $this->authorize('view', $application);
        return response()->json($application->interviewRounds);
    }

    public function store(StoreInterviewRoundRequest $request, string $hash): JsonResponse
    {
        $application = $this->findApp($hash);
        $this->authorize('view', $application);
        $round = $application->interviewRounds()->create($request->validated());
        return response()->json($round, 201);
    }

    public function show(string $hash, InterviewRound $interviewRound): JsonResponse
    {
        $application = $this->findApp($hash);
        $this->authorize('view', $application);
        return response()->json($interviewRound);
    }

    public function update(StoreInterviewRoundRequest $request, string $hash, InterviewRound $interviewRound): JsonResponse
    {
        $application = $this->findApp($hash);
        $this->authorize('update', $application);
        $interviewRound->update($request->validated());
        return response()->json($interviewRound->fresh());
    }

    public function destroy(string $hash, InterviewRound $interviewRound): JsonResponse
    {
        $application = $this->findApp($hash);
        $this->authorize('delete', $application);
        $interviewRound->delete();
        return response()->json(null, 204);
    }
}
