<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    protected $fillable = ['user_id', 'session_id', 'is_admin', 'message', 'is_read'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
