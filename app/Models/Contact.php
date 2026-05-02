<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Contact extends Model
{
    protected $fillable = [
        'application_id', 'name', 'email', 'phone', 'position', 'notes',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }
}
