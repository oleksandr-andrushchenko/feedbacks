<?php

declare(strict_types=1);

namespace App\Service;

use App\Exception\AddressGeocodeFailedException;
use App\Model\Address\Address;
use App\Model\Location;

interface AddressGeocoderInterface
{
    /**
     * @param Location $location
     * @return Address
     * @throws AddressGeocodeFailedException
     */
    public function geocodeAddress(Location $location): Address;
}