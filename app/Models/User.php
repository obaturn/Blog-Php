<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Contracts\Auth\MustVerifyEmail;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_admin',
        'suspended_at',
        'suspension_reason',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'suspended_at' => 'datetime',
        ];
    }

    /**
     * Get the posts for the user.
     */
    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    public function groups()
    {
        return $this->belongsToMany(Group::class, 'group_memberships')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function events()
    {
        return $this->belongsToMany(Event::class, 'event_attendees')->withTimestamps();
    }

    public function conversations()
    {
        return $this->belongsToMany(Conversation::class, 'conversation_user')->withTimestamps();
    }

    public function professionalProfile()
    {
        return $this->hasOne(ProfessionalProfile::class);
    }

    public function jobListings()
    {
        return $this->hasMany(JobListing::class, 'posted_by');
    }

    public function jobApplications()
    {
        return $this->hasMany(JobApplication::class, 'applicant_id');
    }

    public function savedJobs()
    {
        return $this->belongsToMany(JobListing::class, 'saved_jobs')->withTimestamps();
    }

    public function sentConnections()
    {
        return $this->hasMany(Connection::class, 'requester_id');
    }

    public function receivedConnections()
    {
        return $this->hasMany(Connection::class, 'recipient_id');
    }

    public function reports()
    {
        return $this->hasMany(Report::class, 'reporter_id');
    }

    public function blocks()
    {
        return $this->hasMany(UserBlock::class, 'blocker_id');
    }

    public function isBlocking(User $user): bool
    {
        return $this->blocks()->where('blocked_id', $user->id)->exists();
    }

    public function isBlockedBy(User $user): bool
    {
        return $user->isBlocking($this);
    }

    public function endorsementsGiven()
    {
        return $this->hasMany(Endorsement::class, 'endorser_id');
    }

    public function endorsementsReceived()
    {
        return $this->hasMany(Endorsement::class, 'endorsed_user_id');
    }

    public function givenRecommendations()
    {
        return $this->hasMany(Recommendation::class, 'recommender_id');
    }

    public function receivedRecommendations()
    {
        return $this->hasMany(Recommendation::class, 'recommended_user_id');
    }

    public function mentorshipRequestsAsMentor()
    {
        return $this->hasMany(MentorshipRequest::class, 'mentor_id');
    }

    public function mentorshipRequestsAsMentee()
    {
        return $this->hasMany(MentorshipRequest::class, 'mentee_id');
    }

    /**
     * Get the users that this user is following.
     */
    public function following()
    {
        return $this->belongsToMany(
            User::class,
            'follows',
            'follower_id',
            'following_id'
        )->withTimestamps();
    }

    /**
     * Get the users that are following this user.
     */
    public function followers()
    {
        return $this->belongsToMany(
            User::class,
            'follows',
            'following_id',
            'follower_id'
        )->withTimestamps();
    }

    /**
     * Follow a user.
     * 
     * @param User $user
     * @return void
     */
    public function follow(User $user): void
    {
        if ($this->id === $user->id) {
            throw new \Exception('Cannot follow yourself');
        }

        $this->following()->syncWithoutDetaching([$user->id]);
    }

    /**
     * Unfollow a user.
     * 
     * @param User $user
     * @return void
     */
    public function unfollow(User $user): void
    {
        $this->following()->detach($user->id);
    }

    /**
     * Check if this user is following another user.
     * 
     * @param User $user
     * @return bool
     */
    public function isFollowing(User $user): bool
    {
        return $this->following()->where('following_id', $user->id)->exists();
    }

    /**
     * Check if this user is followed by another user.
     * 
     * @param User $user
     * @return bool
     */
    public function isFollowedBy(User $user): bool
    {
        return $this->followers()->where('follower_id', $user->id)->exists();
    }

    /**
     * Get followers count.
     * 
     * @return int
     */
    public function followersCount(): int
    {
        return $this->followers()->count();
    }

    /**
     * Get following count.
     * 
     * @return int
     */
    public function followingCount(): int
    {
        return $this->following()->count();
    }
}
