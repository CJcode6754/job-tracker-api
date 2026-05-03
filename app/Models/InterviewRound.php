<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InterviewRound extends Model
{
    use HasFactory;
    protected $fillable = [
        'application_id', 'date', 'type', 'interviewer_name', 'notes', 'self_rating',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }
}
