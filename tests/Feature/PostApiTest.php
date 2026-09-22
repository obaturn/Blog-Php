<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_create_a_post(): void
    {
        $response = $this->postJson('/api/v1/posts', [
            'title' => 'Hello world',
            'content' => 'This is a test post.',
        ]);

        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_create_a_post(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/posts', [
                'title' => 'My first post',
                'content' => 'This is my social blog entry.',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.post.title', 'My first post');

        $this->assertDatabaseHas('posts', [
            'user_id' => $user->id,
            'title' => 'My first post',
        ]);
    }

    public function test_authenticated_user_can_like_a_post(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/posts/' . $post->id . '/like');

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.likes_count', 1);

        $this->assertDatabaseHas('likes', [
            'user_id' => $user->id,
            'post_id' => $post->id,
        ]);
    }
}
