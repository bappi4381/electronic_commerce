<?php

namespace App\Services\Fraud;

use RuntimeException;

/** Checkout refused; the message is safe to show to the customer, $reason is for logs. */
class OrderBlockedException extends RuntimeException
{
    public function __construct(string $customerMessage, public readonly string $reason)
    {
        parent::__construct($customerMessage);
    }
}
