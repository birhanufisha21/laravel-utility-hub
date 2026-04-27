<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when an external API returns a 4xx or 5xx error response.
 * Controllers catch this and return HTTP 502 to the client.
 */
class ApiException extends RuntimeException
{
    //
}
