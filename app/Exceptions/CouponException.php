<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * A coupon cannot be applied; the message is safe to show to the customer.
 */
class CouponException extends RuntimeException
{
}
