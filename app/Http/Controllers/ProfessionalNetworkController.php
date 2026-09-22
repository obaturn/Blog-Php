<?php

namespace App\Http\Controllers;

use App\Models\Connection;
use App\Models\Endorsement;
use App\Models\Recommendation;
use App\Models\User;
use App\Notifications\SocialActivityNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfessionalNetworkController extends Controller
{
    public function connections(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $connections = Connection::query()
            ->with(['requester:id,name', 'recipient:id,name'])
            ->where('status', 'accepted')
            ->where(fn ($query) => $query
                ->where('requester_id', $userId)
                ->orWhere('recipient_id', $userId))
            ->latest()
            ->paginate(20);

        return response()->json(['success' => true, 'data' => ['connections' => $connections->items()]]);
    }

    public function requestConnection(Request $request, User $user): JsonResponse
    {
        if ($request->user()->is($user)) {
            return response()->json(['success' => false, 'message' => 'You cannot connect with yourself.'], 422);
        }

        if ($request->user()->isBlocking($user) || $request->user()->isBlockedBy($user)) {
            return response()->json(['success' => false, 'message' => 'You cannot connect with this user.'], 403);
        }

        $existing = $this->between($request->user()->id, $user->id);
        if ($existing) {
            return response()->json(['success' => false, 'message' => 'A connection request already exists.'], 409);
        }

        $connection = Connection::create([
            'requester_id' => $request->user()->id,
            'recipient_id' => $user->id,
            'status' => 'pending',
        ]);

        $user->notify(new SocialActivityNotification(
            'connection_requested',
            "{$request->user()->name} wants to connect with you.",
            ['connection_id' => $connection->id, 'user_id' => $request->user()->id]
        ));

        return response()->json(['success' => true, 'data' => ['connection' => $connection]], 201);
    }

    public function respond(Request $request, Connection $connection): JsonResponse
    {
        abort_unless($connection->recipient_id === $request->user()->id, 403);
        $data = $request->validate(['status' => ['required', 'in:accepted,rejected']]);
        abort_unless($connection->status === 'pending', 422, 'This connection request is no longer pending.');

        $connection->update(['status' => $data['status']]);
        if ($data['status'] === 'accepted') {
            $connection->requester->notify(new SocialActivityNotification(
                'connection_accepted',
                "{$request->user()->name} accepted your connection request.",
                ['connection_id' => $connection->id, 'user_id' => $request->user()->id]
            ));
        }

        return response()->json(['success' => true, 'data' => ['connection' => $connection->fresh()]]);
    }

    public function endorse(Request $request, User $user): JsonResponse
    {
        abort_unless($request->user()->id !== $user->id, 422);
        $data = $request->validate(['skill' => ['required', 'string', 'max:60']]);
        $endorsement = Endorsement::firstOrCreate([
            'endorser_id' => $request->user()->id,
            'endorsed_user_id' => $user->id,
            'skill' => $data['skill'],
        ]);

        return response()->json(['success' => true, 'data' => ['endorsement' => $endorsement]], $endorsement->wasRecentlyCreated ? 201 : 200);
    }

    public function endorsements(User $user): JsonResponse
    {
        $endorsements = Endorsement::where('endorsed_user_id', $user->id)
            ->with('endorser:id,name')
            ->latest()
            ->get()
            ->groupBy('skill');

        return response()->json(['success' => true, 'data' => ['endorsements' => $endorsements]]);
    }

    public function recommend(Request $request, User $user): JsonResponse
    {
        abort_unless($request->user()->id !== $user->id, 422);
        $data = $request->validate(['body' => ['required', 'string', 'min:20', 'max:2000']]);
        $recommendation = Recommendation::updateOrCreate(
            ['recommender_id' => $request->user()->id, 'recommended_user_id' => $user->id],
            ['body' => $data['body'], 'status' => 'pending']
        );

        $user->notify(new SocialActivityNotification(
            'recommendation_received',
            "{$request->user()->name} wrote you a professional recommendation.",
            ['recommendation_id' => $recommendation->id]
        ));

        return response()->json(['success' => true, 'data' => ['recommendation' => $recommendation]], 201);
    }

    public function recommendations(User $user): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'recommendations' => Recommendation::where('recommended_user_id', $user->id)
                    ->where('status', 'approved')
                    ->with('recommender:id,name')
                    ->latest()
                    ->get(),
            ],
        ]);
    }

    public function respondRecommendation(Request $request, Recommendation $recommendation): JsonResponse
    {
        abort_unless($recommendation->recommended_user_id === $request->user()->id, 403);
        $data = $request->validate(['status' => ['required', 'in:approved,rejected']]);
        $recommendation->update(['status' => $data['status']]);

        return response()->json(['success' => true, 'data' => ['recommendation' => $recommendation->fresh()]]);
    }

    private function between(int $firstUserId, int $secondUserId): ?Connection
    {
        return Connection::where(function ($query) use ($firstUserId, $secondUserId) {
            $query->where('requester_id', $firstUserId)->where('recipient_id', $secondUserId);
        })->orWhere(function ($query) use ($firstUserId, $secondUserId) {
            $query->where('requester_id', $secondUserId)->where('recipient_id', $firstUserId);
        })->first();
    }
}