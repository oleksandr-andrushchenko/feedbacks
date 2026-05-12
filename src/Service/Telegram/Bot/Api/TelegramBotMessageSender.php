<?php

declare(strict_types=1);

namespace App\Service\Telegram\Bot\Api;

use App\Entity\Telegram\TelegramBot;
use App\Model\Feedback\FeedbackMedia;
use App\Service\Feedback\Media\FeedbackMediaUrlProvider;
use App\Service\Telegram\Bot\TelegramBotRegistry;
use App\Service\Validator\HtmlValidator;
use LogicException;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\ServerResponse;

class TelegramBotMessageSender implements TelegramBotMessageSenderInterface
{
    public function __construct(
        private readonly TelegramBotRegistry $telegramBotRegistry,
        private readonly HtmlValidator $htmlValidator,
        private readonly FeedbackMediaUrlProvider $feedbackMediaUrlProvider,
    )
    {
    }

    public function sendTelegramMessage(
        TelegramBot $botEntity,
        string|int $chatId,
        string $text,
        Keyboard $keyboard = null,
        string $parseMode = 'HTML',
        int $replyToMessageId = null,
        bool $protectContent = null,
        bool $disableWebPagePreview = true,
        bool $keepKeyboard = false,
        ?array $media = null
    ): ServerResponse
    {
        if (count($media ?? []) > 0) {
            return $this->sendMediaMessage($botEntity, $chatId, $media, $text, $parseMode, $protectContent);
        }

        $bot = $this->telegramBotRegistry->getTelegramBot($botEntity);

        $data = [
            'chat_id' => $chatId,
            'text' => $text,
        ];

        if ($replyToMessageId !== null) {
            $data['reply_to_message_id'] = $replyToMessageId;
        }

        if ($parseMode !== null) {
            $data['parse_mode'] = $parseMode;
        }

        if ($keyboard === null) {
            if (!$keepKeyboard) {
                $data['reply_markup'] = Keyboard::remove();
            }
        } else {
            $data['reply_markup'] = $keyboard;
        }

        if ($protectContent !== null) {
            $data['protect_content'] = $protectContent;
        }

        if ($disableWebPagePreview !== null) {
            $data['disable_web_page_preview'] = $disableWebPagePreview;
        }

        if ($parseMode === 'HTML') {
            return $this->sendHtmlMessage($bot, $data);
        }

        return $bot->sendMessage($data);
    }

    private function sendMediaMessage(
        TelegramBot $botEntity,
        string|int $chatId,
        array $media,
        string $caption = null,
        string $parseMode = 'HTML',
        bool $protectContent = null
    ): ServerResponse
    {
        $bot = $this->telegramBotRegistry->getTelegramBot($botEntity);
        $media = array_map(
            static fn (FeedbackMedia|array $item): array => $item instanceof FeedbackMedia ? $item->toArray() : $item,
            array_values($media)
        );

        if (count($media) === 1) {
            $item = $media[0];
            $data = [
                'chat_id' => $chatId,
                $item['type'] === 'video' ? 'video' : 'photo' => $this->getMediaSource($item),
            ];

            if ($caption !== null && $caption !== '') {
                $data['caption'] = $caption;
            }

            if ($parseMode !== null) {
                $data['parse_mode'] = $parseMode;
            }

            if ($protectContent !== null) {
                $data['protect_content'] = $protectContent;
            }

            return $item['type'] === 'video' ? $bot->sendVideo($data) : $bot->sendPhoto($data);
        }

        $items = [];
        foreach ($media as $index => $item) {
            $payload = [
                'type' => $item['type'] === 'video' ? 'video' : 'photo',
                'media' => $this->getMediaSource($item),
            ];

            if ($index === 0 && $caption !== null && $caption !== '') {
                $payload['caption'] = $caption;
            }

            if ($index === 0 && $parseMode !== null) {
                $payload['parse_mode'] = $parseMode;
            }

            $items[] = $payload;
        }

        $data = [
            'chat_id' => $chatId,
            'media' => $items,
        ];

        if ($protectContent !== null) {
            $data['protect_content'] = $protectContent;
        }

        return $bot->sendMediaGroup($data);
    }

    private function getMediaSource(array $media): string
    {
        if (isset($media['telegram_file_id'])) {
            return $media['telegram_file_id'];
        }

        if (isset($media['url'])) {
            return $media['url'];
        }

        return $this->feedbackMediaUrlProvider->getUrl($media);
    }

    private function sendHtmlMessage($bot, array $data, int $max = 4096): ServerResponse
    {
        $text = $data['text'];
        $length = mb_strlen($text);

        if ($length <= $max) {
            return $bot->sendMessage($data);
        }

        if ($text === strip_tags($text)) {
            return $bot->sendMessage($data);
        }

        $response = null;

        while (!empty($text)) {
            $length = min($max, mb_strlen($text));

            while ($length > 0) {
                $chunk = mb_substr($text, 0, $length);

                if ($this->htmlValidator->validateHtml($chunk)) {
                    $data['text'] = $chunk;
                    $response = $bot->sendMessage($data);

                    // Update remaining text
                    $text = mb_substr($text, mb_strlen($chunk));
                    break;
                }

                $length--;
            }

            if ($length === 0) {
                throw new LogicException('Cannot split HTML text into valid chunks within the max size limit.');
            }
        }

        return $response;
    }
}
