<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Coupon extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'type',
        'value',
        'expiry_date',
        'status'
    ];

    protected $casts = [
        'expiry_date' => 'date',
        'status' => 'boolean'
    ];
}
