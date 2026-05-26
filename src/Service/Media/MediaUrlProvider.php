<?php

declare(strict_types=1);

namespace App\Service\Media;

use Aws\S3\S3Client;
use DateInterval;
use DateTimeImmutable;
use InvalidArgumentException;

class MediaUrlProvider
{
    public function __construct(
        private readonly S3Client $s3,
    )
    {
    }

    public function getUrl(
        ?string $storage,
        ?string $bucket,
        ?string $key,
        DateInterval $ttl = new DateInterval('PT15M')
    ): string
    {
        if ($storage !== 's3') {
            throw new InvalidArgumentException(sprintf('Unsupported media storage "%s"', $storage ?? ''));
        }

        $command = $this->s3->getCommand('GetObject', [
            'Bucket' => $bucket,
            'Key' => $key,
        ]);

        return (string) $this->s3
            ->createPresignedRequest($command, (new DateTimeImmutable())->add($ttl))
            ->getUri()
        ;
    }
}
