<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a city name cannot be resolved to geographic coordinates
 * via the Open-Meteo geocoding API. Controllers catch this and return HTTP 404.
 */
class CityNotFoundException extends RuntimeException
{
    //
}
