<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AnnouncementController extends Controller
{
    public function __construct(private NotificationService $notifications) {}

    public function index(Request $request): JsonResponse
    {
        $query = Announcement::with('author:id,name')
            ->orderByDesc('is_pinned')
            ->latest();

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$search}%");
            });
        }

        if ($category = $request->query('category')) {
            $query->where('category', $category);
        }

        return response()->json($query->paginate(15));
    }

    public function show(Announcement $announcement): JsonResponse
    {
        $announcement->load('author:id,name');

        return response()->json($announcement);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'category' => ['required', 'in:general,health,disaster,events,emergency'],
            'image' => ['nullable', 'image', 'max:5120'],
            'is_pinned' => ['boolean'],
        ]);

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('announcements', 'public');
        }

        $announcement = Announcement::create([
            ...$validated,
            'user_id' => $request->user()->id,
        ]);

        \App\Models\User::query()
            ->where('role', 'resident')
            ->where('is_suspended', false)
            ->each(function ($resident) use ($announcement) {
                $this->notifications->notify(
                    $resident,
                    'announcement',
                    'New Announcement',
                    "New announcement: {$announcement->title}",
                    ['announcement_id' => $announcement->id]
                );
            });

        return response()->json($announcement->load('author:id,name'), 201);
    }

    public function update(Request $request, Announcement $announcement): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'content' => ['sometimes', 'string'],
            'category' => ['sometimes', 'in:general,health,disaster,events,emergency'],
            'image' => ['nullable', 'image', 'max:5120'],
            'is_pinned' => ['boolean'],
        ]);

        if ($request->hasFile('image')) {
            if ($announcement->image) {
                Storage::disk('public')->delete($announcement->image);
            }
            $validated['image'] = $request->file('image')->store('announcements', 'public');
        }

        $announcement->update($validated);

        return response()->json($announcement->fresh()->load('author:id,name'));
    }

    public function destroy(Announcement $announcement): JsonResponse
    {
        if ($announcement->image) {
            Storage::disk('public')->delete($announcement->image);
        }

        $announcement->delete();

        return response()->json(['message' => 'Announcement deleted.']);
    }
}
