<?php

declare(strict_types=1);

namespace App\Model\Feedback;

use DateTimeImmutable;
use DateTimeInterface;

class FeedbackMedia
{
    public const string STORAGE_S3 = 's3';
    public const string TYPE_PHOTO = 'photo';
    public const string TYPE_VIDEO = 'video';

    public function __construct(
        private ?string $storage,
        private ?string $bucket,
        private ?string $key,
        private string $type,
        private ?string $telegramFileId = null,
        private ?string $telegramFileUniqueId = null,
        private ?string $mimeType = null,
        private ?int $width = null,
        private ?int $height = null,
        private ?int $fileSize = null,
        private ?string $fileName = null,
        private ?string $groupId = null,
        private ?int $duration = null,
        private ?DateTimeInterface $createdAt = null,
    )
    {
        $this->createdAt ??= new DateTimeImmutable();
    }

    public function getStorage(): ?string
    {
        return $this->storage;
    }

    public function getBucket(): ?string
    {
        return $this->bucket;
    }

    public function getKey(): ?string
    {
        return $this->key;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getTelegramFileId(): ?string
    {
        return $this->telegramFileId;
    }

    public function getTelegramFileUniqueId(): ?string
    {
        return $this->telegramFileUniqueId;
    }

    public function getMimeType(): ?string
    {
        return $this->mimeType;
    }

    public function getWidth(): ?int
    {
        return $this->width;
    }

    public function getHeight(): ?int
    {
        return $this->height;
    }

    public function getFileSize(): ?int
    {
        return $this->fileSize;
    }

    public function getFileName(): ?string
    {
        return $this->fileName;
    }

    public function getGroupId(): ?string
    {
        return $this->groupId;
    }

    public function getDuration(): ?int
    {
        return $this->duration;
    }

    public function getCreatedAt(): ?DateTimeInterface
    {
        return $this->createdAt;
    }

    public function toArray(): array
    {
        return array_filter([
            'storage' => $this->storage,
            'bucket' => $this->bucket,
            'key' => $this->key,
            'type' => $this->type,
            'telegram_file_id' => $this->telegramFileId,
            'telegram_file_unique_id' => $this->telegramFileUniqueId,
            'mime_type' => $this->mimeType,
            'width' => $this->width,
            'height' => $this->height,
            'file_size' => $this->fileSize,
            'file_name' => $this->fileName,
            'group_id' => $this->groupId,
            'duration' => $this->duration,
            'created_at' => $this->createdAt?->format(DateTimeInterface::ATOM),
        ], static fn (mixed $value): bool => $value !== null);
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['storage'] ?? null,
            $data['bucket'] ?? null,
            $data['key'] ?? null,
            $data['type'],
            $data['telegram_file_id'] ?? null,
            $data['telegram_file_unique_id'] ?? null,
            $data['mime_type'] ?? null,
            $data['width'] ?? null,
            $data['height'] ?? null,
            $data['file_size'] ?? null,
            $data['file_name'] ?? null,
            $data['group_id'] ?? null,
            $data['duration'] ?? null,
            isset($data['created_at']) ? new DateTimeImmutable($data['created_at']) : null,
        );
    }
}
