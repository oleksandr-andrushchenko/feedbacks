<?php

declare(strict_types=1);

namespace App\Tests\Fake\Service;

use App\Model\Location;
use App\Service\TimezoneGeocoderInterface;

class FakeTimezoneGeocoder implements TimezoneGeocoderInterface
{
    public function geocodeTimezone(Location $location, int $timestamp = null): string
    {
        return self::timezoneMock();
    }

    public static function timezoneMock(): string
    {
        return 'America/New_York';
    }
}