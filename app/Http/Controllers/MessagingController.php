<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MessagingController extends Controller
{
    public function conversations(Request $request): JsonResponse
    {
        $conversations = $request->user()->conversations()
            ->with(['participants:id,name', 'messages' => fn ($query) => $query->latest()->limit(1)])
            ->withCount(['messages as unread_messages_count' => fn ($query) => $query
                ->whereNull('read_at')
                ->where('sender_id', '!=', $request->user()->id)])
            ->latest('conversations.updated_at')
            ->paginate(min((int) $request->input('per_page', 20), 50));

        return response()->json([
            'success' => true,
            'data' => [
                'conversations' => $conversations->items(),
                'pagination' => [
                    'current_page' => $conversations->currentPage(),
                    'per_page' => $conversations->perPage(),
                    'total' => $conversations->total(),
                    'last_page' => $conversations->lastPage(),
                ],
            ],
        ]);
    }

    public function start(Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id', 'different:' . $request->user()->id],
        ]);
        $otherUser = User::findOrFail($data['user_id']);

        abort_if(
            $request->user()->isBlocking($otherUser) || $request->user()->isBlockedBy($otherUser),
            403,
            'You cannot message this user.'
        );

        $conversation = Conversation::query()
            ->where('type', 'direct')
            ->whereHas('participants', fn ($query) => $query->whereKey($request->user()->id))
            ->whereHas('participants', fn ($query) => $query->whereKey($otherUser->id))
            ->get()
            ->first(fn (Conversation $candidate) => $candidate->participants()->count() === 2);

        if (!$conversation) {
            $conversation = DB::transaction(function () use ($request, $otherUser) {
                $newConversation = Conversation::create(['type' => 'direct']);
                $newConversation->participants()->attach([
                    $request->user()->id,
                    $otherUser->id,
                ]);

                return $newConversation;
            });
        }

        return response()->json([
            'success' => true,
            'data' => ['conversation' => $conversation->load('participants:id,name')],
        ], 201);
    }

    public function messages(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorizeParticipant($request, $conversation);

        $conversation->messages()
            ->where('sender_id', '!=', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        $messages = $conversation->messages()
            ->with('sender:id,name')
            ->oldest()
            ->paginate(min((int) $request->input('per_page', 50), 100));

        return response()->json([
            'success' => true,
            'data' => [
                'messages' => $messages->items(),
                'pagination' => [
                    'current_page' => $messages->currentPage(),
                    'per_page' => $messages->perPage(),
                    'total' => $messages->total(),
                    'last_page' => $messages->lastPage(),
                ],
            ],
        ]);
    }

    public function send(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorizeParticipant($request, $conversation);
        $data = $request->validate(['body' => ['required', 'string', 'max:5000']]);

        $message = $conversation->messages()->create([
            'sender_id' => $request->user()->id,
            'body' => $data['body'],
        ])->load('sender:id,name');

        $conversation->touch();

        return response()->json([
            'success' => true,
            'message' => 'Message sent successfully',
            'data' => ['message' => $message],
        ], 201);
    }

    private function authorizeParticipant(Request $request, Conversation $conversation): void
    {
        abort_unless(
            $conversation->participants()->whereKey($request->user()->id)->exists(),
            403,
            'You are not a participant in this conversation.'
        );
    }
}