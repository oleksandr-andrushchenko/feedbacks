<?php

declare(strict_types=1);

namespace App\Service\Address;

use App\Model\Address\Address;

class AddressInfoProvider
{
    public function getAddressInfo(Address $address): array
    {
        return [
            'country' => $address->getCountry(),
            'administrative_area_level_1' => $address->getAdministrativeAreaLevel1(),
        ];
    }
}