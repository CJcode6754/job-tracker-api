<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function stats(Request $request): JsonResponse
    {
        $user = $request->user();
        $applications = $user->applications()
            ->with('interviewRounds')
            ->get();

        $interviewApps  = $applications->where('status', 'interview');
        $allRounds      = $interviewApps->flatMap(fn($a) => $a->interviewRounds);

        return response()->json([
            'total'     => $applications->count(),
            'active'    => $applications->whereNotIn('status', ['rejected'])->count(),
            'offers'    => $applications->where('status', 'offer')->count(),
            'by_status' => $applications->groupBy('status')->map->count(),
            'by_week'   => $applications
                ->whereNotNull('applied_date')
                ->groupBy(fn($a) => \Carbon\Carbon::parse($a->applied_date)->format('Y-W'))
                ->map->count()
                ->sortKeys(),
            'interviews' => [
                'total_rounds'  => $allRounds->count(),
                'avg_rating'    => ($avg = $allRounds->whereNotNull('self_rating')->avg('self_rating')) ? round($avg, 1) : null,
                'by_type'       => $allRounds->groupBy('type')->map->count(),
                'active_count'  => $interviewApps->count(),
            ],
        ]);
    }
}
