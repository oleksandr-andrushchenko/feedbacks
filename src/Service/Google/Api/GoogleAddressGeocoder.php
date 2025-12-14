<?php

declare(strict_types=1);

namespace App\Service\Google\Api;

use App\Exception\AddressGeocodeFailedException;
use App\Model\Address\Address;
use App\Model\Address\AddressComponent;
use App\Model\Location;
use App\Service\AddressGeocoderInterface;

class GoogleAddressGeocoder implements AddressGeocoderInterface
{
    public function __construct(
        private readonly string $apiKey,
    )
    {
    }

    public function geocodeAddress(Location $location): Address
    {
        $url = sprintf(
            'https://maps.googleapis.com/maps/api/geocode/json?latlng=%s,%s&result_type=%s&key=%s',
            $location->getLatitude(),
            $location->getLongitude(),
            implode('|', [
                'country',
                'administrative_area_level_1',
            ]),
            $this->apiKey
        );

        $json = file_get_contents($url);

        if ($json === false) {
            throw new AddressGeocodeFailedException($location);
        }

        $data = json_decode($json, true);

        if (
            !is_array($data)
            || !isset($data['status'])
            || $data['status'] !== 'OK'
            || !isset($data['results'])
            || !is_array($data['results'])
            || count($data['results']) === 0
        ) {
            throw new AddressGeocodeFailedException($location, $json);
        }

        $results = $data['results'];

        foreach ([true, false] as $alpaOnly) {
            $address = $this->findByAddressComponents($results, alphaOnly: $alpaOnly);

            if ($address !== null) {
                return $address;
            }

            $address = $this->findByCombinedAddressComponents($results, alphaOnly: $alpaOnly);

            if ($address !== null) {
                return $address;
            }
        }

        throw new AddressGeocodeFailedException($location, $json);
    }

    private function findByAddressComponents(array $results, bool $alphaOnly = false): ?Address
    {
        foreach ($results as $result) {
            if (
                !is_array($result)
                || !isset($result['address_components'])
                || !is_array($result['address_components'])
            ) {
                continue;
            }

            $addressComponents = $result['address_components'];

            $country = $this->findAddressComponent('country', $addressComponents);

            if (!$this->validateCountryComponent($country, alphaOnly: $alphaOnly)) {
                continue;
            }

            $administrativeAreaLevel1 = $this->findAddressComponent('administrative_area_level_1', $addressComponents);

            if (!$this->validateAdministrativeAreaLevel1Component($administrativeAreaLevel1, alphaOnly: $alphaOnly)) {
                continue;
            }

            return $this->constructAddressFromComponents($country, $administrativeAreaLevel1);
        }

        return null;
    }

    private function findByCombinedAddressComponents(array $results, bool $alphaOnly = false): ?Address
    {
        $components = [
            'country' => null,
            'administrative_area_level_1' => null,
        ];
        $shouldStop = static fn (): bool => count(array_filter($components)) === count($components);

        foreach ($results as $result) {
            if (
                !is_array($result)
                || !isset($result['address_components'])
                || !is_array($result['address_components'])
            ) {
                continue;
            }

            $addressComponents = $result['address_components'];

            if (!isset($components['country'])) {
                $country = $this->findAddressComponent('country', $addressComponents);

                if ($this->validateCountryComponent($country, alphaOnly: $alphaOnly)) {
                    $components['country'] = $country;
                }

                if ($shouldStop()) {
                    break;
                }
            }

            if (!isset($components['administrative_area_level_1'])) {
                $administrativeAreaLevel1 = $this->findAddressComponent('administrative_area_level_1', $addressComponents);

                if ($this->validateAdministrativeAreaLevel1Component($administrativeAreaLevel1, alphaOnly: $alphaOnly)) {
                    $components['administrative_area_level_1'] = $administrativeAreaLevel1;
                }

                if ($shouldStop()) {
                    break;
                }
            }
        }

        if ($shouldStop()) {
            return $this->constructAddressFromComponents(...$components);
        }

        return null;
    }

    private function findAddressComponent(string $type, $addressComponents): ?AddressComponent
    {
        foreach ($addressComponents as $addressComponent) {
            if (in_array($type, $addressComponent['types'], true)) {
                return new AddressComponent($addressComponent['short_name'], $addressComponent['long_name']);
            }
        }

        return null;
    }

    private function validateCountryComponent(?AddressComponent $country, bool $alphaOnly = false): bool
    {
        if ($country === null) {
            return false;
        }

        if ($alphaOnly && $this->hasNonAlpha($country->getShortName())) {
            return false;
        }

        return true;
    }


    private function validateAdministrativeAreaLevel1Component(?AddressComponent $administrativeAreaLevel1, bool $alphaOnly = false): bool
    {
        if ($administrativeAreaLevel1 === null) {
            return false;
        }

        if ($alphaOnly && $this->hasNonAlpha($administrativeAreaLevel1->getShortName())) {
            return false;
        }

        return true;
    }

    private function constructAddressFromComponents(AddressComponent $country, AddressComponent $administrativeAreaLevel1): Address
    {
        return new Address(
            $country->getShortName(),
            $administrativeAreaLevel1->getShortName()
        );
    }

    private function hasNonAlpha(string $text): bool
    {
        return preg_match('/[^a-zA-Z-\s\–]/i', $text) > 0;
    }
}