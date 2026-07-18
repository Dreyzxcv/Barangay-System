<?php

namespace App\Http\Controllers;

use App\Models\ServiceRequest;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceRequestController extends Controller
{
    public function __construct(private NotificationService $notifications) {}

    public function index(Request $request): JsonResponse
    {
        $query = ServiceRequest::with('user:id,name,email')
            ->latest();

        if (! $request->user()->isAdmin()) {
            $query->where('user_id', $request->user()->id);
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('type', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%"));
            });
        }

        return response()->json($query->paginate(15));
    }

    public function show(Request $request, ServiceRequest $serviceRequest): JsonResponse
    {
        if (! $request->user()->isAdmin() && $serviceRequest->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        return response()->json($serviceRequest->load('user:id,name,email,phone'));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'in:barangay_clearance,certificate_of_residency,certificate_of_indigency,other'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $serviceRequest = ServiceRequest::create([
            ...$validated,
            'user_id' => $request->user()->id,
        ]);

        $this->notifications->notifyAdmins(
            'service_request',
            'New Service Request',
            "New service request: {$serviceRequest->type}",
            ['service_request_id' => $serviceRequest->id]
        );

        return response()->json($serviceRequest, 201);
    }

    public function update(Request $request, ServiceRequest $serviceRequest): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['sometimes', 'in:submitted,reviewing,ready_for_pickup,completed'],
            'admin_notes' => ['nullable', 'string'],
        ]);

        $oldStatus = $serviceRequest->status;
        $serviceRequest->update($validated);

        if (isset($validated['status']) && $validated['status'] !== $oldStatus) {
            $this->notifications->notify(
                $serviceRequest->user,
                'service_request_update',
                'Service Request Updated',
                "Your {$serviceRequest->type} request is now {$serviceRequest->status}.",
                ['service_request_id' => $serviceRequest->id]
            );
        }

        return response()->json($serviceRequest->fresh()->load('user:id,name,email'));
    }
}
