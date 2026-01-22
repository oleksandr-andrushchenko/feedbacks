<?php

declare(strict_types=1);

namespace App\Serializer\Telegram\Bot;

use App\Entity\Telegram\TelegramBotPaymentMethod;
use App\Enum\Telegram\TelegramBotPaymentMethodName;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class TelegramBotPaymentMethodNormalizer implements NormalizerInterface, DenormalizerInterface
{
    /**
     * @param TelegramBotPaymentMethod $data
     * @param string|null $format
     * @param array $context
     * @return array
     */
    public function normalize(mixed $data, string $format = null, array $context = []): array
    {
        return [
            'id' => $data->getId(),
            'telegram_bot_id' => $data->getTelegramBotId(),
            'name' => $data->getName()->value,
            'currency' => $data->getCurrency(),
            'countries' => $data->getCountries() === null ? null : $data->getCountries(),
        ];
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof TelegramBotPaymentMethod && in_array($format, [null], true);
    }

    public function denormalize(mixed $data, string $type, string $format = null, array $context = []): TelegramBotPaymentMethod
    {
        return new TelegramBotPaymentMethod(
            $data['id'],
            null,
            TelegramBotPaymentMethodName::from($data['name']),
            '***',
            $data['currency'],
            $data['countries'] ?? null,
            telegramBotId: $data['telegram_bot_id'] ?? null,
        );
    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return is_array($data) && $type === TelegramBotPaymentMethod::class && in_array($format, [null], true);
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            TelegramBotPaymentMethod::class => false,
        ];
    }
}