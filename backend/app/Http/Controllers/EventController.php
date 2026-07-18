<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventRsvp;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function __construct(private NotificationService $notifications) {}

    public function index(Request $request): JsonResponse
    {
        $query = Event::with(['creator:id,name', 'rsvps'])
            ->where('is_cancelled', false)
            ->where('start_date', '>=', now()->subDay())
            ->orderBy('start_date');

        if ($search = $request->query('search')) {
            $query->where('title', 'like', "%{$search}%");
        }

        return response()->json($query->paginate(15));
    }

    public function show(Event $event): JsonResponse
    {
        return response()->json($event->load(['creator:id,name', 'rsvps.user:id,name']));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'location' => ['nullable', 'string', 'max:500'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $event = Event::create([
            ...$validated,
            'user_id' => $request->user()->id,
        ]);

        \App\Models\User::query()
            ->where('role', 'resident')
            ->where('is_suspended', false)
            ->each(function ($resident) use ($event) {
                $this->notifications->notify(
                    $resident,
                    'event',
                    'New Community Event',
                    "Upcoming event: {$event->title}",
                    ['event_id' => $event->id]
                );
            });

        return response()->json($event->load('creator:id,name'), 201);
    }

    public function update(Request $request, Event $event): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'location' => ['nullable', 'string', 'max:500'],
            'start_date' => ['sometimes', 'date'],
            'end_date' => ['nullable', 'date'],
            'is_cancelled' => ['boolean'],
        ]);

        $event->update($validated);

        if ($event->is_cancelled) {
            $event->rsvps->each(function (EventRsvp $rsvp) use ($event) {
                $this->notifications->notify(
                    $rsvp->user,
                    'event_cancelled',
                    'Event Cancelled',
                    "The event \"{$event->title}\" has been cancelled.",
                    ['event_id' => $event->id]
                );
            });
        }

        return response()->json($event->fresh()->load('creator:id,name'));
    }

    public function destroy(Event $event): JsonResponse
    {
        $event->update(['is_cancelled' => true]);

        return response()->json(['message' => 'Event cancelled.']);
    }

    public function rsvp(Request $request, Event $event): JsonResponse
    {
        if ($event->is_cancelled) {
            return response()->json(['message' => 'Cannot RSVP to a cancelled event.'], 422);
        }

        EventRsvp::firstOrCreate([
            'event_id' => $event->id,
            'user_id' => $request->user()->id,
        ]);

        return response()->json(['message' => 'RSVP confirmed.']);
    }

    public function cancelRsvp(Request $request, Event $event): JsonResponse
    {
        EventRsvp::where('event_id', $event->id)
            ->where('user_id', $request->user()->id)
            ->delete();

        return response()->json(['message' => 'RSVP cancelled.']);
    }
}
