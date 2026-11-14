<?php

namespace App\Models;

use App\Support\BdPhone;
use Illuminate\Database\Eloquent\Model;

class PhoneBlacklist extends Model
{
    protected $guarded = [];

    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }

    public static function isBlocked(?string $phone): bool
    {
        $phone = BdPhone::normalize($phone);

        return $phone !== null && static::where('phone', $phone)->exists();
    }
}
