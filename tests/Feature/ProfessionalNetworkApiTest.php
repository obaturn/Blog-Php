<?php

namespace Tests\Feature;

use App\Models\Connection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfessionalNetworkApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['queue.default' => 'sync']);
    }

    public function test_users_can_request_and_accept_professional_connections(): void
    {
        $requester = User::factory()->create(['name' => 'Amina']);
        $recipient = User::factory()->create(['name' => 'David']);

        $this->actingAs($requester, 'sanctum')
            ->postJson("/api/v1/users/{$recipient->id}/connect")
            ->assertCreated();

        $connection = Connection::firstOrFail();
        $this->actingAs($recipient, 'sanctum')
            ->postJson("/api/v1/connections/{$connection->id}/respond", ['status' => 'accepted'])
            ->assertOk()
            ->assertJsonPath('data.connection.status', 'accepted');

        $this->actingAs($requester, 'sanctum')
            ->getJson('/api/v1/connections')
            ->assertOk()
            ->assertJsonPath('data.connections.0.id', $connection->id);
    }

    public function test_users_can_endorse_and_recommend_a_professional(): void
    {
        $endorser = User::factory()->create();
        $professional = User::factory()->create();

        $this->actingAs($endorser, 'sanctum')
            ->postJson("/api/v1/users/{$professional->id}/endorsements", ['skill' => 'Laravel'])
            ->assertCreated();

        $this->actingAs($endorser, 'sanctum')
            ->postJson("/api/v1/users/{$professional->id}/recommendations", [
                'body' => 'A thoughtful engineer who delivers excellent systems.',
            ])
            ->assertCreated();

        $recommendation = $professional->receivedRecommendations()->firstOrFail();
        $this->actingAs($professional, 'sanctum')
            ->postJson("/api/v1/recommendations/{$recommendation->id}/respond", ['status' => 'approved'])
            ->assertOk();

        $this->getJson("/api/v1/users/{$professional->id}/endorsements")
            ->assertOk()
            ->assertJsonPath('data.endorsements.Laravel.0.skill', 'Laravel');

        $this->getJson("/api/v1/users/{$professional->id}/recommendations")
            ->assertOk()
            ->assertJsonPath('data.recommendations.0.body', 'A thoughtful engineer who delivers excellent systems.');
    }

    public function test_connection_requests_and_recommendations_are_authorized(): void
    {
        $requester = User::factory()->create();
        $recipient = User::factory()->create();
        $outsider = User::factory()->create();

        $this->actingAs($requester, 'sanctum')
            ->postJson("/api/v1/users/{$recipient->id}/connect")
            ->assertCreated();

        $connection = Connection::firstOrFail();
        $this->actingAs($outsider, 'sanctum')
            ->postJson("/api/v1/connections/{$connection->id}/respond", ['status' => 'accepted'])
            ->assertForbidden();
    }
}