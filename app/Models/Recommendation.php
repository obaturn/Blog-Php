<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Recommendation extends Model
{
    protected $fillable = ['recommender_id', 'recommended_user_id', 'body', 'status'];

    public function recommender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recommender_id');
    }

    public function recommendedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recommended_user_id');
    }
}