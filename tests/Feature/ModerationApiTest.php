<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use App\Models\Conversation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModerationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_report_content_and_duplicate_reports_are_reused(): void
    {
        $reporter = User::factory()->create();
        $author = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $author->id]);

        $payload = [
            'reportable_type' => 'post',
            'reportable_id' => $post->id,
            'reason' => 'spam',
            'details' => 'This appears to be promotional spam.',
        ];

        $this->actingAs($reporter, 'sanctum')
            ->postJson('/api/v1/reports', $payload)
            ->assertCreated();

        $this->actingAs($reporter, 'sanctum')
            ->postJson('/api/v1/reports', $payload)
            ->assertOk();

        $this->assertDatabaseCount('reports', 1);
    }

    public function test_admin_can_review_reports_and_suspend_users(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $reporter = User::factory()->create();
        $target = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $target->id]);

        $this->actingAs($reporter, 'sanctum')->postJson('/api/v1/reports', [
            'reportable_type' => 'post',
            'reportable_id' => $post->id,
            'reason' => 'abuse',
        ])->assertCreated();
        $report = Report::firstOrFail();

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v1/moderation/reports/{$report->id}", ['status' => 'resolved'])
            ->assertOk();

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/moderation/users/{$target->id}/suspend", ['reason' => 'Repeated abuse'])
            ->assertOk();

        $this->assertDatabaseHas('audit_logs', ['action' => 'user_suspended', 'auditable_id' => $target->id]);
        $this->assertDatabaseHas('users', ['id' => $target->id]);
        $this->assertNotNull($target->fresh()->suspended_at);
    }

    public function test_non_admin_cannot_review_reports_and_suspended_users_are_blocked(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $reporter = User::factory()->create();
        $target = User::factory()->create();
        $target->update(['suspended_at' => now(), 'suspension_reason' => 'Abuse']);
        $post = Post::factory()->create(['user_id' => $admin->id]);
        $report = Report::create([
            'reporter_id' => $reporter->id,
            'reportable_type' => Post::class,
            'reportable_id' => $post->id,
            'reason' => 'spam',
        ]);

        $this->actingAs($reporter, 'sanctum')
            ->patchJson("/api/v1/moderation/reports/{$report->id}", ['status' => 'dismissed'])
            ->assertForbidden();

        $this->actingAs($target, 'sanctum')
            ->getJson('/api/v1/profile')
            ->assertForbidden();
    }

    public function test_user_can_block_and_unblock_another_user(): void
    {
        $blocker = User::factory()->create();
        $blocked = User::factory()->create();

        $this->actingAs($blocker, 'sanctum')
            ->postJson("/api/v1/users/{$blocked->id}/block")
            ->assertOk();

        $this->assertDatabaseHas('user_blocks', ['blocker_id' => $blocker->id, 'blocked_id' => $blocked->id]);

        $this->actingAs($blocker, 'sanctum')
            ->deleteJson("/api/v1/users/{$blocked->id}/block")
            ->assertOk();

        $this->assertDatabaseMissing('user_blocks', ['blocker_id' => $blocker->id, 'blocked_id' => $blocked->id]);
    }

    public function test_blocked_users_cannot_follow_or_start_conversations(): void
    {
        $blocker = User::factory()->create();
        $blocked = User::factory()->create();

        $this->actingAs($blocker, 'sanctum')
            ->postJson("/api/v1/users/{$blocked->id}/block")
            ->assertOk();

        $this->actingAs($blocker, 'sanctum')
            ->postJson("/api/v1/users/{$blocked->id}/follow")
            ->assertForbidden();

        $this->actingAs($blocker, 'sanctum')
            ->postJson('/api/v1/conversations', ['user_id' => $blocked->id])
            ->assertForbidden();

        $this->assertDatabaseCount('conversations', 0);
    }
}