<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Password;

/**
 * AuthController - Handles user authentication with Sanctum
 *
 * Features:
 * - User registration with automatic login
 * - Secure login with credential validation
 * - Token management (create, revoke, refresh)
 * - Rate limiting for brute force protection
 * - Detailed logging for security monitoring
 */
class AuthController extends Controller
{
    /**
     * Maximum login attempts before lockout.
     */
    protected const MAX_ATTEMPTS = 5;

    /**
     * Lockout duration in minutes.
     */
    protected const LOCKOUT_MINUTES = 15;

    /**
     * Register a new user.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            // Check if email already exists
            if (User::where('email', $request->email)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Registration failed',
                    'error' => 'Email address is already registered.',
                ], 422);
            }

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            $user->sendEmailVerificationNotification();

            // Create token for immediate login after registration
            $token = $user->createToken('auth_token')->plainTextToken;

            Log::info('New user registered', [
                'user_id' => $user->id,
                'email' => $user->email,
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'User registered successfully',
                'data' => [
                    'user' => $this->formatUser($user),
                    'token' => $token,
                    'token_type' => 'Bearer',
                    'token_expires_at' => null, // Sanctum tokens don't expire by default
                ],
            ], 201);
        } catch (\Exception $e) {
            Log::error('Registration failed', [
                'error' => $e->getMessage(),
                'email' => $request->email,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Registration failed',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred during registration',
            ], 500);
        }
    }

    /**
     * Login user and create token.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            // Check for brute force attempts
            if ($this->hasTooManyLoginAttempts($request)) {
                $this->fireLockoutEvent($request);

                return response()->json([
                    'success' => false,
                    'message' => 'Too many login attempts',
                    'error' => 'Please wait ' . self::LOCKOUT_MINUTES . ' minutes before trying again.',
                    'retry_after' => self::LOCKOUT_MINUTES * 60,
                ], 429);
            }

            $user = User::where('email', $request->email)->first();

            if (!$user) {
                $this->incrementLoginAttempts($request);
                throw ValidationException::withMessages([
                    'email' => ['The provided credentials are incorrect.'],
                ]);
            }

            // Verify password
            if (!Hash::check($request->password, $user->password)) {
                $this->incrementLoginAttempts($request);

                Log::warning('Failed login attempt - wrong password', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'ip' => $request->ip(),
                ]);

                throw ValidationException::withMessages([
                    'email' => ['The provided credentials are incorrect.'],
                ]);
            }

            // Check if user is active
            if ($this->isUserInactive($user)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Login failed',
                    'error' => 'Your account has been deactivated. Please contact support.',
                ], 403);
            }

            // Clear login attempts on successful login
            $this->clearLoginAttempts($request);

            // Create new token
            $token = $user->createToken('auth_token')->plainTextToken;

            Log::info('User logged in successfully', [
                'user_id' => $user->id,
                'email' => $user->email,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'data' => [
                    'user' => $this->formatUser($user),
                    'token' => $token,
                    'token_type' => 'Bearer',
                ],
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Login failed',
                'errors' => $e->errors(),
            ], 401);
        } catch (\Exception $e) {
            Log::error('Login error', [
                'error' => $e->getMessage(),
                'email' => $request->email,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Login failed',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred during login',
            ], 500);
        }
    }

    /**
     * Logout user (revoke current token).
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            // Get current token before deletion
            $token = $request->user()->currentAccessToken();
            $tokenId = $token?->id;

            // Revoke current token
            $request->user()->currentAccessToken()->delete();

            Log::info('User logged out', [
                'user_id' => $request->user()->id,
                'token_id' => $tokenId,
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Logout successful',
            ], 200);
        } catch (\Exception $e) {
            Log::error('Logout error', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()?->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Logout failed',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred during logout',
            ], 500);
        }
    }

    /**
     * Logout from all devices (revoke all tokens).
     */
    public function logoutAll(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $tokenCount = $user->tokens()->count();

            // Revoke all tokens
            $user->tokens()->delete();

            Log::info('User logged out from all devices', [
                'user_id' => $user->id,
                'tokens_revoked' => $tokenCount,
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Logged out from all devices successfully',
                'data' => [
                    'tokens_revoked' => $tokenCount,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Logout failed',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred',
            ], 500);
        }
    }

    /**
     * Refresh token (revoke old, create new).
     */
    public function refreshToken(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            // Revoke current token
            $request->user()->currentAccessToken()->delete();

            // Create new token
            $token = $user->createToken('auth_token')->plainTextToken;

            Log::info('Token refreshed', [
                'user_id' => $user->id,
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Token refreshed successfully',
                'data' => [
                    'token' => $token,
                    'token_type' => 'Bearer',
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token refresh failed',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred',
            ], 500);
        }
    }

    /**
     * Get authenticated user profile.
     */
    public function profile(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            // Get token info
            $token = $request->user()->currentAccessToken();

            return response()->json([
                'success' => true,
                'data' => [
                    'user' => $this->formatUser($user),
                    'token_info' => [
                        'id' => $token?->id,
                        'created_at' => $token?->created_at,
                        'expires_at' => $token?->expires_at,
                    ],
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch profile',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred',
            ], 500);
        }
    }

    public function resendVerification(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user->hasVerifiedEmail()) {
            $user->sendEmailVerificationNotification();
        }

        return response()->json(['success' => true, 'message' => 'If verification is needed, an email has been sent.']);
    }

    public function verifyEmail(Request $request, int $id, string $hash): JsonResponse
    {
        abort_unless($request->hasValidSignature(), 403, 'Verification link is invalid or expired.');
        $user = User::findOrFail($id);
        abort_unless(hash_equals($hash, sha1($user->getEmailForVerification())), 403, 'Verification link is invalid.');

        if (!$user->hasVerifiedEmail() && $user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return response()->json(['success' => true, 'message' => 'Email address verified successfully.']);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate(['email' => ['required', 'email']]);
        Password::sendResetLink(['email' => $request->input('email')]);

        return response()->json(['success' => true, 'message' => 'If that email exists, a password reset link has been sent.']);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $status = Password::reset($data, function (User $user, string $password) {
            $user->forceFill(['password' => Hash::make($password)])->save();
            $user->tokens()->delete();
        });

        if ($status !== Password::PASSWORD_RESET) {
            return response()->json(['success' => false, 'message' => __($status)], 422);
        }

        return response()->json(['success' => true, 'message' => 'Password reset successfully.']);
    }

    public function changePassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password:sanctum'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $request->user()->update(['password' => Hash::make($data['password'])]);
        $currentTokenId = $request->user()->currentAccessToken()?->id;
        $request->user()->tokens()->when($currentTokenId, fn ($query) => $query->where('id', '!=', $currentTokenId))->delete();

        return response()->json(['success' => true, 'message' => 'Password changed successfully.']);
    }

    public function deleteAccount(Request $request): JsonResponse
    {
        $request->validate(['password' => ['required', 'current_password:sanctum']]);
        $user = $request->user();
        $user->tokens()->delete();
        $user->delete();

        return response()->json(['success' => true, 'message' => 'Account deleted successfully.']);
    }

    /**
     * Update authenticated user profile.
     */
    public function updateProfile(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'sometimes|string|min:2|max:100',
                'email' => 'sometimes|email|unique:users,email,' . $request->user()->id,
                'bio' => 'sometimes|string|max:500|nullable',
                'avatar' => 'sometimes|image|max:2048|nullable',
            ]);

            $user = $request->user();

            // Update name if provided
            if (isset($validated['name'])) {
                $user->name = $validated['name'];
            }

            // Update email if provided
            if (isset($validated['email'])) {
                $user->email = $validated['email'];
            }

            // Update bio if provided
            if (array_key_exists('bio', $validated)) {
                $user->bio = $validated['bio'];
            }

            // Handle avatar upload
            if ($request->hasFile('avatar')) {
                $avatar = $validated['avatar'];
                $path = $avatar->store('avatars', 'public');
                $user->avatar = $path;
            }

            $user->save();

            Log::info('Profile updated', [
                'user_id' => $user->id,
                'updated_fields' => array_keys($validated),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully',
                'data' => [
                    'user' => $this->formatUser($user),
                ],
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Profile update failed', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()?->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update profile',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred',
            ], 500);
        }
    }

    /**
     * Get all active tokens for the authenticated user.
     */
    public function tokens(Request $request): JsonResponse
    {
        try {
            $tokens = $request->user()->tokens()->get()->map(function ($token) {
                return [
                    'id' => $token->id,
                    'name' => $token->name,
                    'created_at' => $token->created_at,
                    'expires_at' => $token->expires_at,
                    'last_used_at' => $token->last_used_at,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'tokens' => $tokens,
                    'count' => $tokens->count(),
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch tokens',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred',
            ], 500);
        }
    }

    /**
     * Revoke a specific token.
     */
    public function revokeToken(Request $request, $tokenId): JsonResponse
    {
        try {
            $token = $request->user()->tokens()->where('id', $tokenId)->first();

            if (!$token) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token not found',
                ], 404);
            }

            $token->delete();

            Log::info('Token revoked', [
                'user_id' => $request->user()->id,
                'token_id' => $tokenId,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Token revoked successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to revoke token',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred',
            ], 500);
        }
    }

    /**
     * Revoke all tokens for the authenticated user.
     */
    public function revokeAll(Request $request): JsonResponse
    {
        try {
            $count = $request->user()->tokens()->count();
            $request->user()->tokens()->delete();

            Log::info('All tokens revoked', [
                'user_id' => $request->user()->id,
                'tokens_revoked' => $count,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'All tokens revoked successfully',
                'data' => [
                    'tokens_revoked' => $count,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to revoke tokens',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred',
            ], 500);
        }
    }

    // ========== Helper Methods ==========

    /**
     * Format user data for response.
     */
    protected function formatUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'created_at' => $user->created_at,
            'email_verified_at' => $user->email_verified_at,
        ];
    }

    /**
     * Check if user is inactive.
     */
    protected function isUserInactive(User $user): bool
    {
        return $user->suspended_at !== null;
    }

    /**
     * Get the rate limiter key for the given request.
     */
    protected function throttleKey(Request $request): string
    {
        return 'login_attempts_' . strtolower($request->input('email'));
    }

    /**
     * Determine if the user has too many login attempts.
     */
    protected function hasTooManyLoginAttempts(Request $request): bool
    {
        $key = $this->throttleKey($request);
        $attempts = cache($key, 0);

        return $attempts >= self::MAX_ATTEMPTS;
    }

    /**
     * Increment the login attempts for the user.
     */
    protected function incrementLoginAttempts(Request $request): void
    {
        $key = $this->throttleKey($request);
        $attempts = cache($key, 0) + 1;

        cache([$key => $attempts], self::LOCKOUT_MINUTES * 60);
    }

    /**
     * Clear the login attempts for the user.
     */
    protected function clearLoginAttempts(Request $request): void
    {
        cache()->forget($this->throttleKey($request));
    }

    /**
     * Fire the lockout event.
     */
    protected function fireLockoutEvent(Request $request): void
    {
        Log::warning('Too many login attempts - lockout', [
            'email' => $request->input('email'),
            'ip' => $request->ip(),
        ]);
    }
}
