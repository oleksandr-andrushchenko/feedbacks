<?php
declare(strict_types=1);

namespace App\Service\Search\Provider;

use App\Exception\HttpRequesterException;
use Psr\Log\LoggerInterface;
use Throwable;

class SearchProviderCompose
{
    public function __construct(
        private readonly LoggerInterface $logger,
    )
    {
    }

    public function tryCatch(callable $job, mixed $failed, array $ignoreCodes = []): mixed
    {
        try {
            return $job();
        } catch (Throwable $exception) {
            if (!$exception instanceof HttpRequesterException || !in_array($exception->getStatusCode(), $ignoreCodes, true)) {
                $this->logger->error($exception);
            }

            return $failed;
        }
    }
}