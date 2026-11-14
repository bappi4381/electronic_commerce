<?php

namespace App\Rules;

use App\Support\BdPhone;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/** Accepts any common way of writing a Bangladeshi mobile number (+880…, 880…, 01…). */
class BangladeshiMobile implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (BdPhone::normalize(is_string($value) ? $value : null) === null) {
            $fail(__('Enter a valid Bangladeshi mobile number, e.g. 01712345678.'));
        }
    }
}
