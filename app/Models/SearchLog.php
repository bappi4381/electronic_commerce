<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SearchLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['query', 'normalized', 'results_count', 'lang', 'script', 'source', 'user_id', 'session_id'];

    protected $casts = [
        'results_count' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
