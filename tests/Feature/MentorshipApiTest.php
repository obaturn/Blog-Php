<?php

namespace Tests\Feature;

use App\Models\MentorshipRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MentorshipApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['queue.default' => 'sync']);
    }

    public function test_user_can_request_mentorship_and_mentor_can_accept(): void
    {
        $mentor = User::factory()->create(['name' => 'Mentor']);
        $mentor->professionalProfile()->create(['headline' => 'Engineering Mentor']);
        $mentee = User::factory()->create(['name' => 'Mentee']);

        $this->actingAs($mentee, 'sanctum')
            ->postJson("/api/v1/users/{$mentor->id}/mentorships", [
                'goals' => 'I want to improve my backend architecture skills.',
            ])
            ->assertCreated();

        $mentorship = MentorshipRequest::firstOrFail();
        $this->actingAs($mentor, 'sanctum')
            ->postJson("/api/v1/mentorships/{$mentorship->id}/respond", ['status' => 'accepted'])
            ->assertOk()
            ->assertJsonPath('data.mentorship.status', 'accepted');
    }

    public function test_only_mentor_can_respond_and_mentee_can_cancel_pending_request(): void
    {
        $mentor = User::factory()->create();
        $mentor->professionalProfile()->create(['headline' => 'Career Mentor']);
        $mentee = User::factory()->create();
        $outsider = User::factory()->create();
        $mentorship = MentorshipRequest::create([
            'mentor_id' => $mentor->id,
            'mentee_id' => $mentee->id,
            'goals' => 'I want to prepare for technical interviews.',
            'status' => 'pending',
        ]);

        $this->actingAs($outsider, 'sanctum')
            ->postJson("/api/v1/mentorships/{$mentorship->id}/respond", ['status' => 'accepted'])
            ->assertForbidden();

        $this->actingAs($mentee, 'sanctum')
            ->postJson("/api/v1/mentorships/{$mentorship->id}/cancel")
            ->assertOk();

        $this->assertDatabaseHas('mentorship_requests', ['id' => $mentorship->id, 'status' => 'cancelled']);
    }
}