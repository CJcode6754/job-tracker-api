<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Vinkla\Hashids\Facades\Hashids;

class Application extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'company', 'role', 'job_url',
        'location', 'work_type', 'employment_type',
        'status', 'priority', 'applied_date', 'deadline',
        'salary_min', 'salary_max', 'salary_currency', 'notes',
    ];

    protected $appends = ['hash_id'];

    public function getHashIdAttribute(): string
    {
        return Hashids::encode($this->id);
    }

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
