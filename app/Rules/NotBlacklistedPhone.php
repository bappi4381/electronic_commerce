<?php

namespace App\Rules;

use App\Models\PhoneBlacklist;
use App\Support\BdPhone;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Log;

/** Refuses numbers on the admin phone blacklist without telling the customer why. */
class NotBlacklistedPhone implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $phone = BdPhone::normalize(is_string($value) ? $value : null);

        if ($phone && PhoneBlacklist::where('phone', $phone)->exists()) {
            Log::notice('Checkout blocked: blacklisted phone', ['phone' => BdPhone::mask($phone), 'user_id' => auth()->id()]);
            $fail(__('We can\'t take online orders for this number. Please contact us to complete your order.'));
        }
    }
}
