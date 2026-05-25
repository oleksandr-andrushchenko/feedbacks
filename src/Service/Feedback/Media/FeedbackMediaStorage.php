<?php

declare(strict_types=1);

namespace App\Service\Feedback\Media;

use App\Model\Feedback\FeedbackMedia;
use Aws\S3\S3Client;

class FeedbackMediaStorage
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
        string $type,
        ?string $telegramFileId = null,
        ?string $telegramFileUniqueId = null,
        ?string $mimeType = null,
        ?int $width = null,
        ?int $height = null,
        ?int $fileSize = null,
        ?string $fileName = null,
        ?string $groupId = null,
        ?int $duration = null,
    ): FeedbackMedia
    {
        if (!$this->enabled()) {
            return new FeedbackMedia(
                null,
                null,
                null,
                $type,
                $telegramFileId,
                $telegramFileUniqueId,
                $mimeType,
                $width,
                $height,
                $fileSize,
                $fileName,
                $groupId,
                $duration,
            );
        }

        $this->s3->putObject(array_filter([
            'Bucket' => $this->bucket,
            'Key' => $key,
            'Body' => $body,
            'ContentType' => $mimeType,
            'Metadata' => [
                'type' => $type,
                'telegram_file_unique_id' => $telegramFileUniqueId ?? '',
            ],
        ], static fn (mixed $value): bool => $value !== null));

        return new FeedbackMedia(
            FeedbackMedia::STORAGE_S3,
            $this->bucket,
            $key,
            $type,
            $telegramFileId,
            $telegramFileUniqueId,
            $mimeType,
            $width,
            $height,
            $fileSize,
            $fileName,
            $groupId,
            $duration,
        );
    }

    public function enabled(): bool
    {
        return $this->stage === 'prod';
    }
}
