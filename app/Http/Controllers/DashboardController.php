<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function stats(Request $request): JsonResponse
    {
        $base = $request->user()->applications();

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
        ]);
    }
}
