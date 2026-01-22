<?php

declare(strict_types=1);

namespace App\Service\Google\Api;

use App\Exception\TimezoneGeocodeFailedException;
use App\Model\Location;
use App\Service\TimezoneGeocoderInterface;

class GoogleTimezoneGeocoder implements TimezoneGeocoderInterface
{
    public function __construct(
        private readonly string $apiKey,
    )
    {
    }

    public function geocodeTimezone(Location $location, int $timestamp = null): string
    {
        $url = sprintf(
            'https://maps.googleapis.com/maps/api/timezone/json?location=%s%s%s&timestamp=%s&key=%s',
            $location->getLatitude(),
            '%2C',
            $location->getLongitude(),
            $timestamp ?? time(),
            $this->apiKey
        );

        $json = file_get_contents($url);

        if ($json === false) {
            throw new TimezoneGeocodeFailedException($location);
        }

        $data = json_decode($json, true);

        if (
            !is_array($data)
            || !isset($data['status'])
            || $data['status'] !== 'OK'
            || !isset($data['timeZoneId'])
        ) {
            throw new TimezoneGeocodeFailedException($location, $json);
        }

        return $data['timeZoneId'];
    }
}