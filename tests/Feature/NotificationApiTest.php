<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['queue.default' => 'sync']);
    }

    public function test_follow_creates_a_notification_and_it_can_be_marked_read(): void
    {
        $follower = User::factory()->create(['name' => 'Amina']);
        $recipient = User::factory()->create(['name' => 'David']);

        $this->actingAs($follower, 'sanctum')
            ->postJson("/api/v1/users/{$recipient->id}/follow")
            ->assertOk();

        $this->actingAs($recipient, 'sanctum')
            ->getJson('/api/v1/notifications/unread-count')
            ->assertOk()
            ->assertJsonPath('data.unread_count', 1);

        $notification = $recipient->notifications()->firstOrFail();

        $this->actingAs($recipient, 'sanctum')
            ->postJson("/api/v1/notifications/{$notification->id}/read")
            ->assertOk();

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_like_and_comment_notifications_do_not_notify_the_author_about_themselves(): void
    {
        $author = User::factory()->create();
        $reader = User::factory()->create(['name' => 'Reader']);
        $post = Post::factory()->create(['user_id' => $author->id]);

        $this->actingAs($reader, 'sanctum')
            ->postJson("/api/v1/posts/{$post->id}/like")
            ->assertCreated();

        $this->actingAs($reader, 'sanctum')
            ->postJson("/api/v1/posts/{$post->id}/comments", ['body' => 'Useful post'])
            ->assertCreated();

        $this->assertCount(2, $author->notifications);
        $this->assertSame('post_liked', $author->notifications()->oldest()->first()->data['type']);
    }

    public function test_user_can_mark_all_notifications_as_read(): void
    {
        $follower = User::factory()->create();
        $recipient = User::factory()->create();

        $this->actingAs($follower, 'sanctum')
            ->postJson("/api/v1/users/{$recipient->id}/follow")
            ->assertOk();

        $this->actingAs($recipient, 'sanctum')
            ->postJson('/api/v1/notifications/read-all')
            ->assertOk();

        $this->assertSame(0, $recipient->unreadNotifications()->count());
    }
}