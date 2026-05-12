<?php

declare(strict_types=1);

namespace App\Model\Telegram;

readonly class TelegramVideo
{
    public function __construct(
        private string $fileId,
        private string $fileUniqueId,
        private int $width,
        private int $height,
        private int $duration,
        private int $timestamp,
        private ?TelegramPhoto $thumbnail = null,
        private ?int $fileSize = null,
        private ?string $fileName = null,
        private ?string $mimeType = null,
        private ?string $groupId = null,
    )
    {
    }

    public function getFileId(): string
    {
        return $this->fileId;
    }

    public function getFileUniqueId(): string
    {
        return $this->fileUniqueId;
    }

    public function getWidth(): int
    {
        return $this->width;
    }

    public function getHeight(): int
    {
        return $this->height;
    }

    public function getTimestamp(): int
    {
        return $this->timestamp;
    }

    public function getGroupId(): ?string
    {
        return $this->groupId;
    }

    public function getDuration(): int
    {
        return $this->duration;
    }

    public function getThumbnail(): ?TelegramPhoto
    {
        return $this->thumbnail;
    }

    public function getFileSize(): ?int
    {
        return $this->fileSize;
    }

    public function getFileName(): ?string
    {
        return $this->fileName;
    }

    public function getMimeType(): ?string
    {
        return $this->mimeType;
    }
}