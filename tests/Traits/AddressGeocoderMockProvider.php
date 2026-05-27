<?php
declare(strict_types=1);

namespace App\Tests\Traits;

use App\Service\AddressGeocoderInterface;
use PHPUnit\Framework\MockObject\MockObject;

trait AddressGeocoderMockProvider
{
    public function getAddressGeocoderMock(bool $replace = true): AddressGeocoderInterface|MockObject
    {
        $mock = $this->createMock(AddressGeocoderInterface::class);

        if ($replace) {
            static::getContainer()->set('app.address_geocoder', $mock);
        }

        return $mock;
    }
}