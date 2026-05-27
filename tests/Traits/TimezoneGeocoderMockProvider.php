<?php
declare(strict_types=1);

namespace App\Tests\Traits;

use App\Service\TimezoneGeocoderInterface;
use PHPUnit\Framework\MockObject\MockObject;

trait TimezoneGeocoderMockProvider
{
    public function getTimezoneGeocoderMock(bool $replace = true): TimezoneGeocoderInterface|MockObject
    {
        $mock = $this->createMock(TimezoneGeocoderInterface::class);

        if ($replace) {
            static::getContainer()->set('app.timezone_geocoder', $mock);
        }

        return $mock;
    }
}