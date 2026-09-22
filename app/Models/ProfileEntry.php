<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfileEntry extends Model
{
    protected $fillable = [
        'professional_profile_id',
        'type',
        'title',
        'organization',
        'description',
        'started_at',
        'ended_at',
        'is_current',
    ];

    protected $casts = [
        'started_at' => 'date',
        'ended_at' => 'date',
        'is_current' => 'boolean',
    ];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(ProfessionalProfile::class, 'professional_profile_id');
    }
}