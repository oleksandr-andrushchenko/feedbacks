<?php
declare(strict_types=1);

namespace App\Exception;

use Throwable;

class ContactOptionsNotFoundException extends Exception
{
    public function __construct(string $group, int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct(sprintf('"%s" contact options has not been found', $group), $code, $previous);
    }
}