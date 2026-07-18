<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\Event;
use App\Models\Report;
use App\Models\ServiceRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ResidentDashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'announcements' => Announcement::orderByDesc('is_pinned')
                ->latest()
                ->take(5)
                ->get(),
            'active_service_requests' => ServiceRequest::where('user_id', $user->id)
                ->whereNotIn('status', ['completed'])
                ->latest()
                ->take(5)
                ->get(),
            'submitted_reports' => Report::where('user_id', $user->id)
                ->latest()
                ->take(5)
                ->get(),
            'upcoming_events' => Event::where('is_cancelled', false)
                ->where('start_date', '>=', now())
                ->orderBy('start_date')
                ->take(5)
                ->get(),
        ]);
    }
}
