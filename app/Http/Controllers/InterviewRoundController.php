<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInterviewRoundRequest;
use App\Models\Application;
use App\Models\InterviewRound;
use Illuminate\Http\JsonResponse;

class InterviewRoundController extends Controller
{
    public function index(Application $application): JsonResponse
    {
        $this->authorize('view', $application);

        return response()->json($application->interviewRounds);
    }

    public function store(StoreInterviewRoundRequest $request, Application $application): JsonResponse
    {
        $this->authorize('view', $application);

        $round = $application->interviewRounds()->create($request->validated());

        return response()->json($round, 201);
    }

    public function show(Application $application, InterviewRound $interviewRound): JsonResponse
    {
        $this->authorize('view', $application);

        return response()->json($interviewRound);
    }

    public function update(StoreInterviewRoundRequest $request, Application $application, InterviewRound $interviewRound): JsonResponse
    {
        $this->authorize('update', $application);

        $interviewRound->update($request->validated());

        return response()->json($interviewRound->fresh());
    }

    public function destroy(Application $application, InterviewRound $interviewRound): JsonResponse
    {
        $this->authorize('delete', $application);

        $interviewRound->delete();

        return response()->json(null, 204);
    }
}
