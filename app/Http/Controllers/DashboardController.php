<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function stats(Request $request): JsonResponse
    {
        $user = $request->user();
        $base = $user->applications();

        $interviewApps = $base->clone()
            ->where('status', 'interview')
            ->with('interviewRounds')
            ->get();

        $totalRounds    = $interviewApps->sum(fn($a) => $a->interviewRounds->count());
        $avgRating      = $interviewApps
            ->flatMap(fn($a) => $a->interviewRounds)
            ->whereNotNull('self_rating')
            ->avg('self_rating');
        $roundsByType   = $interviewApps
            ->flatMap(fn($a) => $a->interviewRounds)
            ->groupBy('type')
            ->map->count();

        return response()->json([
            'total'     => $base->clone()->count(),
            'active'    => $base->clone()->whereNotIn('status', ['rejected'])->count(),
            'offers'    => $base->clone()->where('status', 'offer')->count(),
            'by_status' => $base->clone()
                ->selectRaw('status, count(*) as count')
                ->groupBy('status')
                ->get(),
            'by_week'   => $base->clone()
                ->selectRaw('DATE_FORMAT(applied_date, "%Y-%u") as week, count(*) as count')
                ->whereNotNull('applied_date')
                ->groupBy('week')
                ->orderBy('week')
                ->get(),
            'interviews' => [
                'total_rounds'   => $totalRounds,
                'avg_rating'     => $avgRating ? round($avgRating, 1) : null,
                'by_type'        => $roundsByType,
                'active_count'   => $interviewApps->count(),
            ],
        ]);
    }
}
