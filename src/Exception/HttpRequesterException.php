<?php
declare(strict_types=1);

namespace App\Exception;

use Throwable;

class HttpRequesterException extends Exception
{
    public function __construct(
        string $method,
        string $url,
        private readonly int $statusCode,
        ?Throwable $previous = null
    )
    {
        parent::__construct(sprintf('%d status code received for "%s %s"', $this->statusCode, $method, $url), 0, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}