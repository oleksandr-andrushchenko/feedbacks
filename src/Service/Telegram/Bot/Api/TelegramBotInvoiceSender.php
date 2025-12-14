<?php

declare(strict_types=1);

namespace App\Service\Telegram\Bot\Api;

use App\Exception\Telegram\Bot\Payment\TelegramBotInvalidCurrencyBotException;
use App\Exception\Telegram\Bot\TelegramBotException;
use App\Model\Telegram\TelegramBotInvoicePhoto;
use App\Service\Telegram\Bot\TelegramBot;
use Longman\TelegramBot\Entities\Payments\LabeledPrice;
use Longman\TelegramBot\Entities\ServerResponse;

class TelegramBotInvoiceSender implements TelegramBotInvoiceSenderInterface
{
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
        $data = [
            'chat_id' => $chatId,
            'title' => $title,
            'description' => $description,
            'payload' => $payload,
            'provider_token' => $providerToken,
            'currency' => $currency,
            'prices' => array_map(static fn (LabeledPrice $price): array => $price->jsonSerialize(), $prices),
        ];

        if ($photo !== null) {
            $data['photo_url'] = $photo->url();

            if ($photo->size() !== null) {
                $data['photo_size'] = $photo->size();
            }

            if ($photo->width() !== null) {
                $data['photo_width'] = $photo->width();
            }

            if ($photo->height() !== null) {
                $data['photo_height'] = $photo->height();
            }
        }

        if ($needPhoneNumber !== null) {
            $data['need_phone_number'] = $needPhoneNumber;
        }

        if ($sendPhoneNumberToProvider) {
            $data['send_phone_number_to_provider'] = $sendPhoneNumberToProvider;
        }

        if ($protectContent !== null) {
            $data['protect_content'] = $protectContent;
        }

        try {
            return $bot->sendInvoice($data);
        } catch (TelegramBotException $exception) {
            if (str_contains($exception->getMessage(), 'CURRENCY_INVALID')) {
                throw new TelegramBotInvalidCurrencyBotException($currency);
            }

            throw $exception;
        }
    }
}