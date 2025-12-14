<?php

declare(strict_types=1);

namespace App\Tests\Fake\Service;

use App\Model\Address\Address;
use App\Model\Location;
use App\Service\AddressGeocoderInterface;

class FakeAddressGeocoder implements AddressGeocoderInterface
{
    private static ?Address $addressMock = null;

    public function geocodeAddress(Location $location): Address
    {
        return self::addressMock();
    }

    public static function addressMock(): Address
    {
        return static::$addressMock ?? new Address('ca', 'QC');
    }

    public static function setAddressMock(Address $addressMock): void
    {
        self::$addressMock = $addressMock;
    }
}