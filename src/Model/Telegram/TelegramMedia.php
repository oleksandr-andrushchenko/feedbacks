<?php

declare(strict_types=1);

namespace App\Model\Telegram;

use DateTimeImmutable;
use DateTimeInterface;

class TelegramMedia
{
    public const string TYPE_PHOTO = 'photo';
    public const string TYPE_VIDEO = 'video';

    public function __construct(
        private readonly ?string $storage,
        private readonly ?string $bucket,
        private readonly ?string $key,
        private readonly string $type,
        private readonly ?string $telegramFileId = null,
        private readonly ?string $telegramFileUniqueId = null,
        private readonly ?string $mimeType = null,
        private readonly ?int $width = null,
        private readonly ?int $height = null,
        private readonly ?int $fileSize = null,
        private readonly ?string $fileName = null,
        private readonly ?string $groupId = null,
        private readonly ?int $duration = null,
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
}
