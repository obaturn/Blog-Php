<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Report;
use App\Models\User;
use App\Models\UserBlock;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ModerationController extends Controller
{
    public function report(Request $request): JsonResponse
    {
        $data = $request->validate([
            'reportable_type' => ['required', 'in:post,comment,user,job,event,message'],
            'reportable_id' => ['required', 'integer', 'min:1'],
            'reason' => ['required', 'in:spam,harassment,abuse,misinformation,impersonation,other'],
            'details' => ['nullable', 'string', 'max:2000'],
        ]);

        if ($data['reportable_type'] === 'user' && (int) $data['reportable_id'] === $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'You cannot report yourself.'], 422);
        }

        $model = [
            'post' => \App\Models\Post::class,
            'comment' => \App\Models\Comment::class,
            'user' => User::class,
            'job' => \App\Models\JobListing::class,
            'event' => \App\Models\Event::class,
            'message' => \App\Models\Message::class,
        ][$data['reportable_type']];

        abort_unless($model::whereKey($data['reportable_id'])->exists(), 404);

        $report = Report::firstOrCreate([
            'reporter_id' => $request->user()->id,
            'reportable_type' => $model,
            'reportable_id' => $data['reportable_id'],
        ], [
            'reason' => $data['reason'],
            'details' => $data['details'] ?? null,
            'status' => 'pending',
        ]);

        return response()->json(['success' => true, 'data' => ['report' => $report]], $report->wasRecentlyCreated ? 201 : 200);
    }

    public function block(Request $request, User $user): JsonResponse
    {
        abort_unless($request->user()->id !== $user->id, 422);
        UserBlock::firstOrCreate([
            'blocker_id' => $request->user()->id,
            'blocked_id' => $user->id,
        ], ['reason' => $request->input('reason')]);

        return response()->json(['success' => true, 'message' => 'User blocked.']);
    }

    public function unblock(Request $request, User $user): JsonResponse
    {
        UserBlock::where('blocker_id', $request->user()->id)->where('blocked_id', $user->id)->delete();
        return response()->json(['success' => true, 'message' => 'User unblocked.']);
    }

    public function reports(Request $request): JsonResponse
    {
        $this->authorizeAdmin($request);
        $reports = Report::with(['reporter:id,name', 'reviewer:id,name'])->latest()->paginate(30);
        return response()->json(['success' => true, 'data' => ['reports' => $reports->items()]]);
    }

    public function reviewReport(Request $request, Report $report): JsonResponse
    {
        $this->authorizeAdmin($request);
        $data = $request->validate(['status' => ['required', 'in:reviewing,resolved,dismissed']]);
        $report->update(['status' => $data['status'], 'reviewed_by' => $request->user()->id, 'reviewed_at' => now()]);
        AuditLog::create([
            'actor_id' => $request->user()->id,
            'action' => 'report_reviewed',
            'auditable_type' => Report::class,
            'auditable_id' => $report->id,
            'metadata' => ['status' => $data['status']],
            'created_at' => now(),
        ]);
        return response()->json(['success' => true, 'data' => ['report' => $report->fresh()]]);
    }

    public function suspend(Request $request, User $user): JsonResponse
    {
        $this->authorizeAdmin($request);
        $data = $request->validate(['reason' => ['required', 'string', 'max:1000']]);
        $user->update(['suspended_at' => now(), 'suspension_reason' => $data['reason']]);
        $user->tokens()->delete();
        AuditLog::create([
            'actor_id' => $request->user()->id,
            'action' => 'user_suspended',
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
            'metadata' => ['reason' => $data['reason']],
            'created_at' => now(),
        ]);
        return response()->json(['success' => true, 'message' => 'User suspended.']);
    }

    private function authorizeAdmin(Request $request): void
    {
        abort_unless($request->user()->is_admin, 403, 'Administrator access required.');
    }
}