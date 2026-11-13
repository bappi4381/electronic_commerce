<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * A courier API call failed; the message is safe to show to administrators.
 */
class CourierException extends RuntimeException
{
}
