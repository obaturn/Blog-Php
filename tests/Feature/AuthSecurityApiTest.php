<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AuthSecurityApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
    }

    public function test_registration_sends_email_verification_and_signed_link_verifies_user(): void
    {
        $response = $this->postJson('/api/v1/register', [
            'name' => 'Verified User',
            'email' => 'verified@example.com',
            'password' => 'Password123!@',
            'password_confirmation' => 'Password123!@',
        ])->assertCreated();

        $user = User::where('email', 'verified@example.com')->firstOrFail();
        Notification::assertSentTo($user, \Illuminate\Auth\Notifications\VerifyEmail::class);

        $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(10), [
            'id' => $user->id,
            'hash' => sha1($user->getEmailForVerification()),
        ]);

        $this->getJson(parse_url($url, PHP_URL_PATH) . '?' . parse_url($url, PHP_URL_QUERY))
            ->assertOk();
        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_user_can_change_password_and_delete_account(): void
    {
        $user = User::factory()->create(['password' => bcrypt('OldPassword123!')]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/password/change', [
                'current_password' => 'OldPassword123!',
                'password' => 'NewPassword123!@',
                'password_confirmation' => 'NewPassword123!@',
            ])
            ->assertOk();

        $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/v1/account', ['password' => 'NewPassword123!@'])
            ->assertOk();

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_password_reset_request_has_non_enumerating_response(): void
    {
        $this->postJson('/api/v1/forgot-password', ['email' => 'unknown@example.com'])
            ->assertOk()
            ->assertJsonPath('message', 'If that email exists, a password reset link has been sent.');
    }
}