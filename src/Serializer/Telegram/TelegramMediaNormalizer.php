<?php

declare(strict_types=1);

namespace App\Serializer\Telegram;

use App\Model\Telegram\TelegramMedia;
use App\Model\Telegram\TelegramPhoto;
use App\Model\Telegram\TelegramVideo;
use DateTimeImmutable;
use DateTimeInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class TelegramMediaNormalizer implements NormalizerInterface, DenormalizerInterface
{
    public function normalize(mixed $data, ?string $format = null, array $context = []): array
    {
        if ($data instanceof TelegramPhoto) {
            return [
                'model' => 'photo',
                'file_id' => $data->getFileId(),
                'file_unique_id' => $data->getFileUniqueId(),
                'width' => $data->getWidth(),
                'height' => $data->getHeight(),
                'timestamp' => $data->getTimestamp(),
                'file_size' => $data->getFileSize(),
                'group_id' => $data->getGroupId(),
            ];
        }

        if ($data instanceof TelegramVideo) {
            return [
                'model' => 'video',
                'file_id' => $data->getFileId(),
                'file_unique_id' => $data->getFileUniqueId(),
                'width' => $data->getWidth(),
                'height' => $data->getHeight(),
                'duration' => $data->getDuration(),
                'timestamp' => $data->getTimestamp(),
                'thumbnail' => $data->getThumbnail() === null ? null : $this->normalize($data->getThumbnail(), $format, $context),
                'file_size' => $data->getFileSize(),
                'file_name' => $data->getFileName(),
                'mime_type' => $data->getMimeType(),
                'group_id' => $data->getGroupId(),
            ];
        }

        if ($data instanceof TelegramMedia) {
            return array_filter([
                'storage' => $data->getStorage(),
                'bucket' => $data->getBucket(),
                'key' => $data->getKey(),
                'type' => $data->getType(),
                'telegram_file_id' => $data->getTelegramFileId(),
                'telegram_file_unique_id' => $data->getTelegramFileUniqueId(),
                'mime_type' => $data->getMimeType(),
                'width' => $data->getWidth(),
                'height' => $data->getHeight(),
                'file_size' => $data->getFileSize(),
                'file_name' => $data->getFileName(),
                'group_id' => $data->getGroupId(),
                'duration' => $data->getDuration(),
                'created_at' => $data->getCreatedAt()?->format(DateTimeInterface::ATOM),
            ], static fn (mixed $value): bool => $value !== null);
        }

        return [];
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof TelegramPhoto || $data instanceof TelegramVideo || $data instanceof TelegramMedia;
    }

    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        return match ($data['model'] ?? null) {
            'photo' => new TelegramPhoto(
                $data['file_id'],
                $data['file_unique_id'],
                $data['width'],
                $data['height'],
                $data['timestamp'],
                $data['file_size'] ?? null,
                $data['group_id'] ?? null,
            ),
            'video' => new TelegramVideo(
                $data['file_id'],
                $data['file_unique_id'],
                $data['width'],
                $data['height'],
                $data['duration'],
                $data['timestamp'],
                thumbnail: isset($data['thumbnail']) && is_array($data['thumbnail']) ? $this->denormalize($data['thumbnail'], TelegramPhoto::class, $format, $context) : null,
                fileSize: $data['file_size'] ?? null,
                fileName: $data['file_name'] ?? null,
                mimeType: $data['mime_type'] ?? null,
                groupId: $data['group_id'] ?? null,
            ),
            default => new TelegramMedia(
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
            )
        };
    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return is_array($data) && in_array($type, [TelegramPhoto::class, TelegramVideo::class, TelegramMedia::class], true);
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            TelegramPhoto::class => true,
            TelegramVideo::class => true,
            TelegramMedia::class => true,
        ];
    }
}
