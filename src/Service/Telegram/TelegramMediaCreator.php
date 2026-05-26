<?php

declare(strict_types=1);

namespace App\Service\Telegram;

use App\Model\Telegram\TelegramMedia;
use App\Model\Telegram\TelegramPhoto;
use App\Model\Telegram\TelegramVideo;
use App\Service\Media\MediaStorage;
use App\Service\Telegram\Bot\TelegramBot;
use RuntimeException;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class TelegramMediaCreator
{
    public function __construct(
        private readonly MediaStorage $mediaStorage,
        private readonly HttpClientInterface $httpClient,
    )
    {
    }

    public function createTelegramMedia(TelegramBot $bot, TelegramPhoto|TelegramVideo $transfer): TelegramMedia
    {
        $mimeType = $transfer instanceof TelegramVideo ? $transfer->getMimeType() : 'image/jpeg';
        $type = $transfer instanceof TelegramPhoto ? TelegramMedia::TYPE_PHOTO : TelegramMedia::TYPE_VIDEO;

        if (!$this->mediaStorage->enabled()) {
            return new TelegramMedia(
                null,
                null,
                null,
                $type,
                $transfer->getFileId(),
                $transfer->getFileUniqueId(),
                $mimeType,
                $transfer->getWidth(),
                $transfer->getHeight(),
                $transfer->getFileSize(),
                $transfer instanceof TelegramVideo ? $transfer->getFileName() : null,
                $transfer->getGroupId(),
                $transfer instanceof TelegramVideo ? $transfer->getDuration() : null,
            );
        }

        $response = $bot->getFile(['file_id' => $transfer->getFileId()]);
        $file = $response->getResult();
        $filePath = $file?->getFilePath();

        if ($filePath === null) {
            throw new RuntimeException(sprintf('Unable to resolve Telegram file path for "%s"', $transfer->getFileUniqueId()));
        }

        $extension = pathinfo($filePath, PATHINFO_EXTENSION) ?: match ($mimeType) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'video/mp4' => 'mp4',
            default => $type === TelegramMedia::TYPE_VIDEO ? 'mp4' : 'jpg',
        };
        $key = sprintf(
            'feedback-media/%s/%s.%s',
            date('Y/m/d', $transfer->getTimestamp()),
            $transfer->getFileUniqueId(),
            $extension
        );
        $url = sprintf(
            'https://api.telegram.org/file/bot%s/%s',
            $bot->getEntity()->getToken(),
            $filePath
        );

        $this->mediaStorage->put(
            $this->httpClient->request('GET', $url)->getContent(),
            $key,
            $mimeType,
        );

        return new TelegramMedia(
            $this->mediaStorage->getStorage(),
            $this->mediaStorage->getBucket(),
            $key,
            $type,
            $transfer->getFileId(),
            $transfer->getFileUniqueId(),
            $mimeType,
            $transfer->getWidth(),
            $transfer->getHeight(),
            $transfer->getFileSize(),
            $transfer instanceof TelegramVideo ? $transfer->getFileName() : null,
            $transfer->getGroupId(),
            $transfer instanceof TelegramVideo ? $transfer->getDuration() : null,
        );
    }
}