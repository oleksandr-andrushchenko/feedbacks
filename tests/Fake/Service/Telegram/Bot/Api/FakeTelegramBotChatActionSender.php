<?php
declare(strict_types=1);

namespace App\Tests\Fake\Service\Telegram\Bot\Api;

use App\Service\Telegram\Bot\Api\TelegramBotChatActionSenderInterface;
use App\Service\Telegram\Bot\TelegramBot;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Request;

class FakeTelegramBotChatActionSender implements TelegramBotChatActionSenderInterface
{
    public function __construct(private array $calls = [])
    {
    }

    public function getCalls(): array
    {
        return $this->calls;
    }

    public function sendChatAction(TelegramBot $bot, int $chatId, string $action): ServerResponse
    {
        $this->calls[] = func_get_args();

        return Request::emptyResponse();
    }
}