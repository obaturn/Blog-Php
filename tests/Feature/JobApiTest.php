<?php

namespace Tests\Feature;

use App\Models\JobListing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JobApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['queue.default' => 'sync']);
    }

    public function test_user_can_create_search_save_and_apply_to_a_job(): void
    {
        $employer = User::factory()->create();
        $applicant = User::factory()->create();

        $this->actingAs($employer, 'sanctum')
            ->postJson('/api/v1/jobs', [
                'title' => 'Laravel Engineer',
                'company_name' => 'Acme Labs',
                'description' => 'Build reliable APIs.',
                'workplace_type' => 'remote',
                'employment_type' => 'full_time',
            ])
            ->assertCreated();

        $job = JobListing::firstOrFail();

        $this->getJson('/api/v1/jobs?search=Laravel')
            ->assertOk()
            ->assertJsonPath('data.jobs.0.title', 'Laravel Engineer');

        $this->actingAs($applicant, 'sanctum')
            ->postJson("/api/v1/jobs/{$job->id}/save")
            ->assertOk();

        $this->actingAs($applicant, 'sanctum')
            ->postJson("/api/v1/jobs/{$job->id}/apply", [
                'cover_letter' => 'I would love to join your team.',
            ])
            ->assertCreated()
            ->assertJsonPath('data.application.status', 'submitted');

        $this->assertDatabaseHas('saved_jobs', ['job_listing_id' => $job->id, 'user_id' => $applicant->id]);
        $this->assertDatabaseHas('job_applications', ['job_listing_id' => $job->id, 'applicant_id' => $applicant->id]);
    }

    public function test_applicant_cannot_apply_twice_to_the_same_job(): void
    {
        $employer = User::factory()->create();
        $applicant = User::factory()->create();
        $job = JobListing::create([
            'posted_by' => $employer->id,
            'title' => 'Designer',
            'company_name' => 'Studio',
            'description' => 'Design products.',
            'workplace_type' => 'hybrid',
            'employment_type' => 'contract',
        ]);

        $payload = ['cover_letter' => 'Application letter'];
        $this->actingAs($applicant, 'sanctum')->postJson("/api/v1/jobs/{$job->id}/apply", $payload)->assertCreated();
        $this->actingAs($applicant, 'sanctum')->postJson("/api/v1/jobs/{$job->id}/apply", $payload)->assertConflict();
    }

    public function test_closed_jobs_do_not_accept_applications(): void
    {
        $employer = User::factory()->create();
        $applicant = User::factory()->create();
        $job = JobListing::create([
            'posted_by' => $employer->id,
            'title' => 'Closed role',
            'company_name' => 'Studio',
            'description' => 'No longer open.',
            'workplace_type' => 'onsite',
            'employment_type' => 'part_time',
            'status' => 'closed',
        ]);

        $this->actingAs($applicant, 'sanctum')
            ->postJson("/api/v1/jobs/{$job->id}/apply", ['cover_letter' => 'Application'])
            ->assertStatus(422);
    }

    public function test_job_owner_can_review_applications_and_close_listing(): void
    {
        $employer = User::factory()->create();
        $applicant = User::factory()->create();
        $job = JobListing::create([
            'posted_by' => $employer->id,
            'title' => 'Platform Engineer',
            'company_name' => 'Acme',
            'description' => 'Build platforms.',
            'workplace_type' => 'remote',
            'employment_type' => 'full_time',
        ]);
        $application = $job->applications()->create([
            'applicant_id' => $applicant->id,
            'cover_letter' => 'I am interested.',
            'status' => 'submitted',
        ]);

        $this->actingAs($employer, 'sanctum')
            ->getJson("/api/v1/jobs/{$job->id}/applications")
            ->assertOk()
            ->assertJsonPath('data.applications.0.id', $application->id);

        $this->actingAs($employer, 'sanctum')
            ->patchJson("/api/v1/jobs/{$job->id}/applications/{$application->id}", ['status' => 'interview'])
            ->assertOk()
            ->assertJsonPath('data.application.status', 'interview');

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $applicant->id,
            'type' => 'App\\Notifications\\SocialActivityNotification',
        ]);

        $this->actingAs($employer, 'sanctum')
            ->postJson("/api/v1/jobs/{$job->id}/close")
            ->assertOk();

        $this->assertDatabaseHas('job_listings', ['id' => $job->id, 'status' => 'closed']);
    }

    public function test_only_owner_can_manage_listing_and_applicant_can_withdraw(): void
    {
        $employer = User::factory()->create();
        $applicant = User::factory()->create();
        $outsider = User::factory()->create();
        $job = JobListing::create([
            'posted_by' => $employer->id,
            'title' => 'QA Engineer',
            'company_name' => 'Acme',
            'description' => 'Test products.',
            'workplace_type' => 'hybrid',
            'employment_type' => 'contract',
        ]);
        $application = $job->applications()->create([
            'applicant_id' => $applicant->id,
            'cover_letter' => 'Please consider me.',
            'status' => 'submitted',
        ]);

        $this->actingAs($outsider, 'sanctum')
            ->getJson("/api/v1/jobs/{$job->id}/applications")
            ->assertForbidden();

        $this->actingAs($applicant, 'sanctum')
            ->postJson("/api/v1/job-applications/{$application->id}/withdraw")
            ->assertOk();

        $this->assertDatabaseHas('job_applications', ['id' => $application->id, 'status' => 'withdrawn']);
    }

    public function test_job_owner_cannot_apply_to_own_listing(): void
    {
        $employer = User::factory()->create();
        $job = JobListing::create([
            'posted_by' => $employer->id,
            'title' => 'Own role',
            'company_name' => 'My Company',
            'description' => 'Own listing.',
            'workplace_type' => 'onsite',
            'employment_type' => 'full_time',
        ]);

        $this->actingAs($employer, 'sanctum')
            ->postJson("/api/v1/jobs/{$job->id}/apply", ['cover_letter' => 'My application'])
            ->assertForbidden();
    }
}