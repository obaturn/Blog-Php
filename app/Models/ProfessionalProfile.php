<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProfessionalProfile extends Model
{
    protected $fillable = [
        'user_id',
        'headline',
        'summary',
        'location',
        'website_url',
        'github_url',
        'linkedin_url',
        'skills',
        'visibility',
        'availability',
    ];

    protected $casts = ['skills' => 'array'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function entries(): HasMany
    {
        return $this->hasMany(ProfileEntry::class)->orderByDesc('started_at');
    }
}