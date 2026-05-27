<?php
declare(strict_types=1);

namespace App\Service\Telegram\Bot\Api;

use App\Service\Telegram\Bot\TelegramBot;
use Longman\TelegramBot\Entities\ServerResponse;

class TelegramBotChatActionSender implements TelegramBotChatActionSenderInterface
{
    public function sendChatAction(TelegramBot $bot, int $chatId, string $action): ServerResponse
    {
        $data = [
            'chat_id' => $chatId,
            'action' => $action,
        ];

        return $bot->sendChatAction($data);
    }
}