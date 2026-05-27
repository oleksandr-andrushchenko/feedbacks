<?php
declare(strict_types=1);

namespace App\Service\Media;

use Aws\S3\S3Client;

class MediaStorage
{
    public function __construct(
        private readonly S3Client $s3,
        private readonly string $bucket,
        private readonly string $stage,
    )
    {
    }

    public function put(
        string $body,
        string $key,
        ?string $mimeType = null,
    ): void
    {
        if (!$this->enabled()) {
            return;
        }

        $this->s3->putObject([
            'Bucket' => $this->bucket,
            'Key' => $key,
            'Body' => $body,
            'ContentType' => $mimeType,
        ]);
    }

    public function enabled(): bool
    {
        return $this->stage === 'prod';
    }

    public function getStorage(): string
    {
        return 's3';
    }

    public function getBucket(): string
    {
        return $this->bucket;
    }
}
