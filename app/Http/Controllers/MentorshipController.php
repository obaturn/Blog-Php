<?php

namespace App\Http\Controllers;

use App\Models\MentorshipRequest;
use App\Models\User;
use App\Notifications\SocialActivityNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MentorshipController extends Controller
{
    public function request(Request $request, User $mentor): JsonResponse
    {
        abort_unless($request->user()->id !== $mentor->id, 422);
        abort_unless($mentor->professionalProfile()->exists(), 422, 'This user has no professional mentor profile.');

        $data = $request->validate([
            'goals' => ['required', 'string', 'min:20', 'max:2000'],
            'scheduled_at' => ['nullable', 'date', 'after:now'],
        ]);

        $existing = MentorshipRequest::where('mentor_id', $mentor->id)
            ->where('mentee_id', $request->user()->id)
            ->whereIn('status', ['pending', 'accepted'])
            ->exists();
        if ($existing) {
            return response()->json(['success' => false, 'message' => 'An active mentorship request already exists.'], 409);
        }

        $mentorship = MentorshipRequest::create([
            ...$data,
            'mentor_id' => $mentor->id,
            'mentee_id' => $request->user()->id,
            'status' => 'pending',
        ]);

        $mentor->notify(new SocialActivityNotification(
            'mentorship_requested',
            "{$request->user()->name} requested mentorship from you.",
            ['mentorship_id' => $mentorship->id]
        ));

        return response()->json(['success' => true, 'data' => ['mentorship' => $mentorship]], 201);
    }

    public function index(Request $request): JsonResponse
    {
        $requests = MentorshipRequest::query()
            ->with(['mentor:id,name', 'mentee:id,name'])
            ->where(fn ($query) => $query
                ->where('mentor_id', $request->user()->id)
                ->orWhere('mentee_id', $request->user()->id))
            ->latest()
            ->paginate(20);

        return response()->json(['success' => true, 'data' => ['mentorships' => $requests->items()]]);
    }

    public function respond(Request $request, MentorshipRequest $mentorship): JsonResponse
    {
        abort_unless($mentorship->mentor_id === $request->user()->id, 403);
        $data = $request->validate(['status' => ['required', 'in:accepted,rejected']]);
        abort_unless($mentorship->status === 'pending', 422, 'This mentorship request is no longer pending.');

        $mentorship->update(['status' => $data['status']]);
        $mentorship->mentee->notify(new SocialActivityNotification(
            'mentorship_request_status_changed',
            "Your mentorship request was {$data['status']}.",
            ['mentorship_id' => $mentorship->id, 'status' => $data['status']]
        ));

        return response()->json(['success' => true, 'data' => ['mentorship' => $mentorship->fresh()]]);
    }

    public function cancel(Request $request, MentorshipRequest $mentorship): JsonResponse
    {
        abort_unless($mentorship->mentee_id === $request->user()->id, 403);
        abort_unless($mentorship->status === 'pending', 422, 'Only pending requests can be cancelled.');
        $mentorship->update(['status' => 'cancelled']);

        return response()->json(['success' => true, 'message' => 'Mentorship request cancelled.']);
    }
}