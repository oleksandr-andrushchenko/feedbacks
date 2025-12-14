<?php

declare(strict_types=1);

namespace App\Exception;

use App\Model\Location;
use Throwable;

class TimezoneGeocodeFailedException extends Exception
{
    public function __construct(
        private readonly Location $location,
        private readonly mixed $content = null,
        int $code = 0,
        ?Throwable $previous = null
    )
    {
        parent::__construct(
            sprintf(
                'Unable to geocode timezone by "%s, %s" location',
                $this->location->getLatitude(),
                $this->location->getLongitude()
            ),
            $code,
            $previous
        );
    }

    public function getContent(): mixed
    {
        return $this->content;
    }
}