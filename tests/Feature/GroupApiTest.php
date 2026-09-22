<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GroupApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_and_join_a_public_group(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();

        $createResponse = $this->actingAs($owner, 'sanctum')
            ->postJson('/api/v1/groups', [
                'name' => 'Laravel Builders',
                'description' => 'A community for Laravel developers.',
            ]);

        $createResponse->assertCreated()
            ->assertJsonPath('data.group.name', 'Laravel Builders');

        $group = Group::firstOrFail();

        $this->actingAs($member, 'sanctum')
            ->postJson("/api/v1/groups/{$group->id}/join")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('group_memberships', [
            'group_id' => $group->id,
            'user_id' => $member->id,
            'role' => 'member',
        ]);
    }

    public function test_non_member_cannot_publish_to_a_group(): void
    {
        $owner = User::factory()->create();
        $outsider = User::factory()->create();
        $group = Group::create([
            'owner_id' => $owner->id,
            'name' => 'Private Engineering',
            'slug' => 'private-engineering',
            'is_private' => true,
        ]);
        $group->members()->attach($owner->id, ['role' => 'owner']);

        $this->actingAs($outsider, 'sanctum')
            ->postJson('/api/v1/posts', [
                'title' => 'Unauthorized group post',
                'content' => 'This should not be published.',
                'group_id' => $group->id,
            ])
            ->assertForbidden()
            ->assertJsonPath('success', false);
    }

    public function test_member_can_publish_to_a_group(): void
    {
        $owner = User::factory()->create();
        $group = Group::create([
            'owner_id' => $owner->id,
            'name' => 'Open Source Circle',
            'slug' => 'open-source-circle',
        ]);
        $group->members()->attach($owner->id, ['role' => 'owner']);

        $this->actingAs($owner, 'sanctum')
            ->postJson('/api/v1/posts', [
                'title' => 'Welcome to the group',
                'content' => 'Our first community post.',
                'group_id' => $group->id,
            ])
            ->assertCreated()
            ->assertJsonPath('data.post.group_id', $group->id);
    }
}