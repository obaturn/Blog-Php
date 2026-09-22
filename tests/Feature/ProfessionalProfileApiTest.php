<?php

namespace Tests\Feature;

use App\Models\ProfileEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfessionalProfileApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_a_professional_profile_and_add_experience(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/professional-profile', [
                'headline' => 'Senior Laravel Engineer',
                'summary' => 'I build reliable web platforms.',
                'skills' => ['Laravel', 'PHP', 'React'],
                'availability' => 'open_to_work',
            ])
            ->assertOk()
            ->assertJsonPath('data.profile.headline', 'Senior Laravel Engineer');

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/professional-profile/entries', [
                'type' => 'experience',
                'title' => 'Backend Engineer',
                'organization' => 'Acme',
                'is_current' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('data.entry.title', 'Backend Engineer');

        $this->assertDatabaseHas('profile_entries', [
            'professional_profile_id' => $user->professionalProfile()->value('id'),
            'title' => 'Backend Engineer',
        ]);
    }

    public function test_public_profile_can_be_viewed_and_private_profile_is_hidden(): void
    {
        $user = User::factory()->create();
        $profile = $user->professionalProfile()->create([
            'headline' => 'Product Designer',
            'visibility' => 'public',
        ]);

        $this->getJson("/api/v1/users/{$user->id}/professional-profile")
            ->assertOk()
            ->assertJsonPath('data.profile.id', $profile->id);

        $profile->update(['visibility' => 'private']);

        $this->getJson("/api/v1/users/{$user->id}/professional-profile")
            ->assertNotFound();
    }

    public function test_user_cannot_delete_another_users_profile_entry(): void
    {
        $owner = User::factory()->create();
        $outsider = User::factory()->create();
        $entry = $owner->professionalProfile()->create()->entries()->create([
            'type' => 'project',
            'title' => 'Portfolio project',
        ]);

        $this->actingAs($outsider, 'sanctum')
            ->deleteJson("/api/v1/professional-profile/entries/{$entry->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('profile_entries', ['id' => $entry->id]);
    }
}