<?php
declare(strict_types=1);

namespace App\Service\Telegram\Bot\Api;

use App\Service\Telegram\Bot\TelegramBot;
use Longman\TelegramBot\Entities\ServerResponse;

interface TelegramBotChatActionSenderInterface
{
    public function sendChatAction(TelegramBot $bot, int $chatId, string $action): ServerResponse;
}