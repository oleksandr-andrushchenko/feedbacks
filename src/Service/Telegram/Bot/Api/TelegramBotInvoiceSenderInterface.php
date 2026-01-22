<?php

declare(strict_types=1);

namespace App\Service\Telegram\Bot\Api;

use App\Exception\Telegram\Bot\Payment\TelegramBotInvalidCurrencyBotException;
use App\Model\Telegram\TelegramBotInvoicePhoto;
use App\Service\Telegram\Bot\TelegramBot;
use Longman\TelegramBot\Entities\ServerResponse;

interface TelegramBotInvoiceSenderInterface
{
    /**
     * @param TelegramBot $bot
     * @param string $chatId
     * @param string $title
     * @param string $description
     * @param string $payload
     * @param string $providerToken
     * @param string $currency
     * @param array $prices
     * @param TelegramBotInvoicePhoto|null $photo
     * @param bool $needPhoneNumber
     * @param bool $sendPhoneNumberToProvider
     * @param bool $protectContent
     * @return ServerResponse
     * @throws TelegramBotInvalidCurrencyBotException
     */
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
    ): ServerResponse;
}