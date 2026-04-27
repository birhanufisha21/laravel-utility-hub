<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when an external API returns HTTP 429 (Too Many Requests).
 * Controllers catch this and return HTTP 429 to the client, with a warning log.
 */
class RateLimitException extends RuntimeException
{
    //
}
