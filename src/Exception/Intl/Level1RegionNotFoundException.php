<?php
declare(strict_types=1);

namespace App\Exception\Intl;

use App\Exception\Exception;
use Throwable;

class Level1RegionNotFoundException extends Exception
{
    public function __construct(string $level1Region, int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct(sprintf('"%s" level 1 region has not been found', $level1Region), $code, $previous);
    }
}
