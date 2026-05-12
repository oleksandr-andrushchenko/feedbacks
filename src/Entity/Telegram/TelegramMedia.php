<?php

declare(strict_types=1);

namespace App\Entity\Telegram;

use DateTimeImmutable;
use DateTimeInterface;
use OA\Dynamodb\Attribute\Attribute;
use OA\Dynamodb\Attribute\Entity;
use OA\Dynamodb\Attribute\GlobalIndex;
use OA\Dynamodb\Attribute\PartitionKey;
use OA\Dynamodb\Attribute\SortKey;
use Stringable;

// todo: add auto-expiration

#[Entity(
    new PartitionKey('TELEGRAM_MEDIA', ['id']),
    new SortKey('META'),
    [
        new GlobalIndex(
            'TELEGRAM_MEDIA_BY_CONVERSATION',
            new PartitionKey(null, ['conversationId'], 'telegram_media_conversation_pk')
        ),
    ]
)]
class TelegramMedia implements Stringable
{
    public const string TYPE_PHOTO = 'photo';
    public const string TYPE_VIDEO = 'video';

    public function __construct(
        #[Attribute('telegram_file_unique_id')]
        private string $id,
        #[Attribute('telegram_file_id')]
        private string $fileId,
        #[Attribute]
        private string $type,
        #[Attribute('conversation_id')]
        private ?string $conversationId = null,
        #[Attribute]
        private ?int $width = null,
        #[Attribute]
        private ?int $height = null,
        #[Attribute('file_size')]
        private ?int $fileSize = null,
        #[Attribute('file_name')]
        private ?string $fileName = null,
        #[Attribute('mime_type')]
        private ?string $mimeType = null,
        #[Attribute('group_id')]
        private ?string $groupId = null,
        #[Attribute]
        private ?int $duration = null,
        #[Attribute('thumb_id')]
        private ?string $thumbId = null,
        #[Attribute('created_at')]
        private ?DateTimeInterface $createdAt = null,
    )
    {
        $this->createdAt ??= new DateTimeImmutable();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function getFileId(): string
    {
        return $this->fileId;
    }

    public function setFileId(string $fileId): void
    {
        $this->fileId = $fileId;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function photo(): bool
    {
        return $this->type === self::TYPE_PHOTO;
    }

    public function video(): bool
    {
        return $this->type === self::TYPE_VIDEO;
    }

    public function getConversationId(): ?string
    {
        return $this->conversationId;
    }

    public function setConversationId(?string $conversationId): void
    {
        $this->conversationId = $conversationId;
    }

    public function getWidth(): ?int
    {
        return $this->width;
    }

    public function setWidth(?int $width): void
    {
        $this->width = $width;
    }

    public function getHeight(): ?int
    {
        return $this->height;
    }

    public function setHeight(?int $height): void
    {
        $this->height = $height;
    }

    public function getFileSize(): ?int
    {
        return $this->fileSize;
    }

    public function setFileSize(?int $fileSize): void
    {
        $this->fileSize = $fileSize;
    }

    public function getFileName(): ?string
    {
        return $this->fileName;
    }

    public function setFileName(?string $fileName): void
    {
        $this->fileName = $fileName;
    }

    public function getMimeType(): ?string
    {
        return $this->mimeType;
    }

    public function setMimeType(?string $mimeType): void
    {
        $this->mimeType = $mimeType;
    }

    public function getGroupId(): ?string
    {
        return $this->groupId;
    }

    public function setGroupId(?string $groupId): void
    {
        $this->groupId = $groupId;
    }

    public function getDuration(): ?int
    {
        return $this->duration;
    }

    public function setDuration(?int $duration): void
    {
        $this->duration = $duration;
    }

    public function getThumbId(): ?string
    {
        return $this->thumbId;
    }

    public function setThumbId(?string $thumbId): void
    {
        $this->thumbId = $thumbId;
    }

    public function getCreatedAt(): ?DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?DateTimeInterface $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function __toString(): string
    {
        return $this->id;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'file_id' => $this->fileId,
            'type' => $this->type,
            'conversation_id' => $this->conversationId,
            'width' => $this->width,
            'height' => $this->height,
            'file_size' => $this->fileSize,
            'file_name' => $this->fileName,
            'mime_type' => $this->mimeType,
            'group_id' => $this->groupId,
            'duration' => $this->duration,
            'thumb_id' => $this->thumbId,
            'created_at' => $this->createdAt?->format(DateTimeInterface::ATOM),
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['id'],
            $data['file_id'],
            $data['type'],
            $data['conversation_id'] ?? null,
            $data['width'] ?? null,
            $data['height'] ?? null,
            $data['file_size'] ?? null,
            $data['file_name'] ?? null,
            $data['mime_type'] ?? null,
            $data['group_id'] ?? null,
            $data['duration'] ?? null,
            $data['thumb_id'] ?? null,
            isset($data['created_at']) ? new DateTimeImmutable($data['created_at']) : null,
        );
    }
}
