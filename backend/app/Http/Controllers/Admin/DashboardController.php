<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Report;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function stats(): JsonResponse
    {
        return response()->json([
            'total_residents' => User::where('role', 'resident')->count(),
            'pending_reports' => Report::where('status', 'pending')->count(),
            'pending_requests' => ServiceRequest::whereIn('status', ['submitted', 'reviewing'])->count(),
            'active_announcements' => Announcement::count(),
            'reports_by_status' => Report::selectRaw('status, count(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status'),
            'requests_by_status' => ServiceRequest::selectRaw('status, count(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status'),
            'recent_reports' => Report::with('user:id,name')->latest()->take(5)->get(),
            'recent_requests' => ServiceRequest::with('user:id,name')->latest()->take(5)->get(),
        ]);
    }
}
