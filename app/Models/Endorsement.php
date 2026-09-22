<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Endorsement extends Model
{
    protected $fillable = ['endorser_id', 'endorsed_user_id', 'skill'];

    public function endorser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'endorser_id');
    }

    public function endorsedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'endorsed_user_id');
    }
}