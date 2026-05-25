<?php

declare(strict_types=1);

namespace App\Service\Feedback\Media;

use App\Model\Feedback\FeedbackMedia;
use Aws\S3\S3Client;
use DateInterval;
use DateTimeImmutable;
use InvalidArgumentException;

class FeedbackMediaUrlProvider
{
    public function __construct(
        private readonly S3Client $s3,
    )
    {
    }

    public function getUrl(array|FeedbackMedia $media, DateInterval $ttl = new DateInterval('PT15M')): string
    {
        $media = $media instanceof FeedbackMedia ? $media->toArray() : $media;

        if (($media['storage'] ?? null) !== FeedbackMedia::STORAGE_S3) {
            throw new InvalidArgumentException(sprintf('Unsupported feedback media storage "%s"', $media['storage'] ?? ''));
        }

        $command = $this->s3->getCommand('GetObject', [
            'Bucket' => $media['bucket'],
            'Key' => $media['key'],
        ]);

        return (string) $this->s3
            ->createPresignedRequest($command, (new DateTimeImmutable())->add($ttl))
            ->getUri()
        ;
    }
}
