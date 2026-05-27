<?php
declare(strict_types=1);

namespace App\Model\Telegram;

readonly class TelegramPhoto
{
    public function __construct(
        private string $fileId,
        private string $fileUniqueId,
        private int $width,
        private int $height,
        private int $timestamp,
        private ?int $fileSize = null,
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

    public function getFileSize(): ?int
    {
        return $this->fileSize;
    }

    public function getGroupId(): ?string
    {
        return $this->groupId;
    }
}