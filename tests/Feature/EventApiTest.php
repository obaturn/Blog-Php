<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_and_attend_an_event(): void
    {
        $host = User::factory()->create();
        $attendee = User::factory()->create();

        $response = $this->actingAs($host, 'sanctum')
            ->postJson('/api/v1/events', [
                'title' => 'Laravel workshop',
                'description' => 'A practical workshop.',
                'starts_at' => now()->addDay()->toIso8601String(),
                'ends_at' => now()->addDay()->addHours(2)->toIso8601String(),
                'location' => 'Online',
                'max_attendees' => 2,
            ]);

        $response->assertCreated()->assertJsonPath('data.event.title', 'Laravel workshop');
        $event = Event::firstOrFail();

        $this->actingAs($attendee, 'sanctum')
            ->postJson("/api/v1/events/{$event->id}/attend")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('event_attendees', [
            'event_id' => $event->id,
            'user_id' => $attendee->id,
        ]);
    }

    public function test_group_event_requires_group_membership(): void
    {
        $host = User::factory()->create();
        $outsider = User::factory()->create();
        $group = Group::create([
            'owner_id' => $host->id,
            'name' => 'Community Hosts',
            'slug' => 'community-hosts',
        ]);
        $group->members()->attach($host->id, ['role' => 'owner']);

        $eventResponse = $this->actingAs($host, 'sanctum')
            ->postJson('/api/v1/events', [
                'title' => 'Members meetup',
                'starts_at' => now()->addDay()->toIso8601String(),
                'ends_at' => now()->addDay()->addHour()->toIso8601String(),
                'group_id' => $group->id,
            ]);
        $eventResponse->assertCreated();
        $event = Event::firstOrFail();

        $this->actingAs($outsider, 'sanctum')
            ->postJson("/api/v1/events/{$event->id}/attend")
            ->assertForbidden();
    }

    public function test_event_capacity_is_enforced(): void
    {
        $host = User::factory()->create();
        $attendee = User::factory()->create();
        $event = Event::create([
            'host_id' => $host->id,
            'title' => 'Small meetup',
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->addHour(),
            'max_attendees' => 1,
        ]);
        $event->attendees()->attach($host->id);

        $this->actingAs($attendee, 'sanctum')
            ->postJson("/api/v1/events/{$event->id}/attend")
            ->assertStatus(422)
            ->assertJsonPath('message', 'This event is full.');
    }
}