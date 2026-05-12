<?php

declare(strict_types=1);

namespace App\Service\Telegram;

use App\Model\Feedback\FeedbackMedia;
use App\Model\Telegram\TelegramPhoto;
use App\Model\Telegram\TelegramVideo;
use App\Service\Feedback\Media\FeedbackMediaStorage;
use App\Service\Telegram\Bot\TelegramBot;
use RuntimeException;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class TelegramMediaCreator
{
    public function __construct(
        private readonly FeedbackMediaStorage $feedbackMediaStorage,
        private readonly HttpClientInterface $httpClient,
    )
    {
    }

    public function createTelegramMedia(TelegramBot $bot, TelegramPhoto|TelegramVideo $transfer): FeedbackMedia
    {
        $mimeType = $transfer instanceof TelegramVideo ? $transfer->getMimeType() : 'image/jpeg';
        $type = $transfer instanceof TelegramPhoto ? FeedbackMedia::TYPE_PHOTO : FeedbackMedia::TYPE_VIDEO;

        if (!$this->feedbackMediaStorage->enabled()) {
            return new FeedbackMedia(
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

        $extension = pathinfo($filePath, PATHINFO_EXTENSION) ?: $this->guessExtension($mimeType, $type);
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

        return $this->feedbackMediaStorage->put(
            $this->httpClient->request('GET', $url)->getContent(),
            $key,
            $type,
            $transfer->getFileId(),
            $transfer->getFileUniqueId(),
            $mimeType,
            width: $transfer->getWidth(),
            height: $transfer->getHeight(),
            fileSize: $transfer->getFileSize(),
            fileName: $transfer instanceof TelegramVideo ? $transfer->getFileName() : null,
            groupId: $transfer->getGroupId(),
            duration: $transfer instanceof TelegramVideo ? $transfer->getDuration() : null,
        );
    }

    private function guessExtension(?string $mimeType, string $type): string
    {
        return match ($mimeType) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'video/mp4' => 'mp4',
            default => $type === FeedbackMedia::TYPE_VIDEO ? 'mp4' : 'jpg',
        };
    }
}
