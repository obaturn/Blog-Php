<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MessagingApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_can_start_a_direct_conversation_and_exchange_messages(): void
    {
        $sender = User::factory()->create(['name' => 'Sender']);
        $recipient = User::factory()->create(['name' => 'Recipient']);

        $start = $this->actingAs($sender, 'sanctum')
            ->postJson('/api/v1/conversations', ['user_id' => $recipient->id]);

        $start->assertCreated()
            ->assertJsonPath('data.conversation.participants.0.name', 'Sender');

        $conversation = Conversation::firstOrFail();

        $this->actingAs($sender, 'sanctum')
            ->postJson("/api/v1/conversations/{$conversation->id}/messages", ['body' => 'Hello there'])
            ->assertCreated()
            ->assertJsonPath('data.message.body', 'Hello there');

        $this->actingAs($recipient, 'sanctum')
            ->getJson("/api/v1/conversations/{$conversation->id}/messages")
            ->assertOk()
            ->assertJsonPath('data.messages.0.body', 'Hello there');

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'sender_id' => $sender->id,
            'body' => 'Hello there',
        ]);
    }

    public function test_non_participant_cannot_read_or_send_messages(): void
    {
        $sender = User::factory()->create();
        $recipient = User::factory()->create();
        $outsider = User::factory()->create();
        $conversation = Conversation::create(['type' => 'direct']);
        $conversation->participants()->attach([$sender->id, $recipient->id]);

        $this->actingAs($outsider, 'sanctum')
            ->getJson("/api/v1/conversations/{$conversation->id}/messages")
            ->assertForbidden();

        $this->actingAs($outsider, 'sanctum')
            ->postJson("/api/v1/conversations/{$conversation->id}/messages", ['body' => 'Intruder'])
            ->assertForbidden();
    }

    public function test_existing_direct_conversation_is_reused(): void
    {
        $first = User::factory()->create();
        $second = User::factory()->create();

        $this->actingAs($first, 'sanctum')
            ->postJson('/api/v1/conversations', ['user_id' => $second->id])
            ->assertCreated();

        $this->actingAs($second, 'sanctum')
            ->postJson('/api/v1/conversations', ['user_id' => $first->id])
            ->assertCreated();

        $this->assertDatabaseCount('conversations', 1);
    }
}