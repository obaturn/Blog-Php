<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Group;
use App\Notifications\SocialActivityNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EventController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $events = Event::query()
            ->with(['host:id,name', 'group:id,name,slug'])
            ->withCount('attendees')
            ->where('starts_at', '>=', now())
            ->when($request->input('search'), function ($query, $search) {
                $query->where(function ($eventQuery) use ($search) {
                    $eventQuery->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('location', 'like', "%{$search}%");
                });
            })
            ->orderBy('starts_at')
            ->paginate(min((int) $request->input('per_page', 15), 50));

        $events->getCollection()->transform(function (Event $event) use ($request) {
            $event->setAttribute(
                'is_attending',
                $request->user()?->events()->whereKey($event->id)->exists() ?? false
            );

            return $event;
        });

        return response()->json([
            'success' => true,
            'data' => [
                'events' => $events->items(),
                'pagination' => [
                    'current_page' => $events->currentPage(),
                    'per_page' => $events->perPage(),
                    'total' => $events->total(),
                    'last_page' => $events->lastPage(),
                ],
            ],
        ]);
    }

    public function show(Request $request, Event $event): JsonResponse
    {
        $event->load(['host:id,name', 'group:id,name,slug'])->loadCount('attendees');
        $event->setAttribute(
            'is_attending',
            $request->user()?->events()->whereKey($event->id)->exists() ?? false
        );

        return response()->json(['success' => true, 'data' => ['event' => $event]]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'starts_at' => ['required', 'date', 'after:now'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'location' => ['nullable', 'string', 'max:255'],
            'meeting_url' => ['nullable', 'url', 'max:2048'],
            'max_attendees' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'group_id' => ['nullable', 'integer', 'exists:groups,id'],
        ]);

        $group = isset($data['group_id']) ? Group::findOrFail($data['group_id']) : null;
        if ($group && !$request->user()->groups()->whereKey($group->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'You must belong to the group before hosting an event there.',
            ], 403);
        }

        $event = Event::create([
            ...$data,
            'host_id' => $request->user()->id,
        ]);
        $event->attendees()->attach($request->user()->id);

        return response()->json([
            'success' => true,
            'message' => 'Event created successfully',
            'data' => ['event' => $event->load(['host:id,name', 'group:id,name,slug'])],
        ], 201);
    }

    public function attend(Request $request, Event $event): JsonResponse
    {
        return DB::transaction(function () use ($request, $event) {
            $event = Event::query()->lockForUpdate()->findOrFail($event->id);

            if ($event->group_id && !$request->user()->groups()->whereKey($event->group_id)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Join the event\'s group before attending.',
                ], 403);
            }

            if ($event->attendees()->whereKey($request->user()->id)->exists()) {
                return response()->json(['success' => true, 'message' => 'You are already attending.']);
            }

            if ($event->max_attendees !== null && $event->attendees()->count() >= $event->max_attendees) {
                return response()->json([
                    'success' => false,
                    'message' => 'This event is full.',
                ], 422);
            }

            $event->attendees()->attach($request->user()->id);

            if ($event->host_id !== $request->user()->id) {
                $event->host->notify(new SocialActivityNotification(
                    'event_attendee_joined',
                    "{$request->user()->name} is attending your event.",
                    ['event_id' => $event->id, 'user_id' => $request->user()->id]
                ));
            }

            return response()->json(['success' => true, 'message' => 'You are attending this event.']);
        });
    }

    public function leave(Request $request, Event $event): JsonResponse
    {
        if ($event->host_id === $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'The event host cannot leave their own event.',
            ], 422);
        }

        $event->attendees()->detach($request->user()->id);

        return response()->json(['success' => true, 'message' => 'You left this event.']);
    }
}