<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Application extends Model
{
    protected $fillable = [
        'user_id', 'company', 'role', 'job_url', 'status',
        'priority', 'applied_date', 'deadline',
        'salary_min', 'salary_max', 'salary_currency', 'notes',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }

    public function interviewRounds(): HasMany
    {
        return $this->hasMany(InterviewRound::class);
    }
}
