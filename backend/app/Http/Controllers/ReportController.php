<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(private NotificationService $notifications) {}

    public function index(Request $request): JsonResponse
    {
        $query = Report::with('user:id,name,email')
            ->latest();

        if (! $request->user()->isAdmin()) {
            $query->where('user_id', $request->user()->id);
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($category = $request->query('category')) {
            $query->where('category', $category);
        }

        if ($request->user()->isAdmin() && $dateFrom = $request->query('date_from')) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($request->user()->isAdmin() && $dateTo = $request->query('date_to')) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        return response()->json($query->paginate(15));
    }

    public function show(Request $request, Report $report): JsonResponse
    {
        if (! $request->user()->isAdmin() && $report->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        return response()->json($report->load('user:id,name,email,phone'));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'category' => ['required', 'in:garbage,broken_streetlight,road_damage,flooding,noise_complaint,stray_animals,other'],
            'photo' => ['nullable', 'image', 'max:5120'],
            'location_address' => ['nullable', 'string', 'max:500'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
        ]);

        if ($request->hasFile('photo')) {
            $validated['photo'] = $request->file('photo')->store('reports', 'public');
        }

        $report = Report::create([
            ...$validated,
            'user_id' => $request->user()->id,
        ]);

        $this->notifications->notifyAdmins(
            'report',
            'New Community Report',
            "New report submitted: {$report->title}",
            ['report_id' => $report->id]
        );

        return response()->json($report, 201);
    }

    public function update(Request $request, Report $report): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['sometimes', 'in:pending,in_progress,resolved,closed'],
            'assigned_to' => ['nullable', 'string', 'max:255'],
            'admin_notes' => ['nullable', 'string'],
        ]);

        $oldStatus = $report->status;
        $report->update($validated);

        if (isset($validated['status']) && $validated['status'] !== $oldStatus) {
            $this->notifications->notify(
                $report->user,
                'report_update',
                'Report Status Updated',
                "Your report \"{$report->title}\" is now {$report->status}.",
                ['report_id' => $report->id]
            );
        }

        return response()->json($report->fresh()->load('user:id,name,email'));
    }

    public function export(Request $request): StreamedResponse
    {
        $query = Report::with('user:id,name,email')->latest();

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($category = $request->query('category')) {
            $query->where('category', $category);
        }

        if ($dateFrom = $request->query('date_from')) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo = $request->query('date_to')) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $reports = $query->get();

        return response()->streamDownload(function () use ($reports) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['ID', 'Title', 'Category', 'Status', 'Resident', 'Email', 'Location', 'Submitted']);

            foreach ($reports as $report) {
                fputcsv($handle, [
                    $report->id,
                    $report->title,
                    $report->category,
                    $report->status,
                    $report->user->name,
                    $report->user->email,
                    $report->location_address,
                    $report->created_at->toDateTimeString(),
                ]);
            }

            fclose($handle);
        }, 'reports-'.now()->format('Y-m-d').'.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }
}
