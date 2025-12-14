<?php

declare(strict_types=1);

namespace App\Tests\Fake\Service\Telegram\Bot\Api;

use App\Model\Telegram\TelegramBotInvoicePhoto;
use App\Service\Telegram\Bot\Api\TelegramBotInvoiceSenderInterface;
use App\Service\Telegram\Bot\TelegramBot;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Request;

class FakeTelegramBotInvoiceSender implements TelegramBotInvoiceSenderInterface
{
    public function __construct(private array $calls = [])
    {
    }

    public function getCalls(): array
    {
        return $this->calls;
    }

    public function sendInvoice(
        TelegramBot $bot,
        string $chatId,
        string $title,
        string $description,
        string $payload,
        string $providerToken,
        string $currency,
        array $prices,
        TelegramBotInvoicePhoto $photo = null,
        bool $needPhoneNumber = true,
        bool $sendPhoneNumberToProvider = true,
        bool $protectContent = true,
    ): ServerResponse
    {
        $this->calls[] = func_get_args();

        return Request::emptyResponse();
    }
}
