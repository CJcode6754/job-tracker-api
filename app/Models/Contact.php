<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Contact extends Model
{
    use HasFactory;

    protected $fillable = [
        'application_id', 'name', 'email', 'phone', 'position', 'notes',
    ];

    protected $hidden = ['application_id'];

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }
}
