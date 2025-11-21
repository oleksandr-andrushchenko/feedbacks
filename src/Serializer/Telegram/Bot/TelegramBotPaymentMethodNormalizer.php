<?php

declare(strict_types=1);

namespace App\Serializer\Telegram\Bot;

use App\Entity\Telegram\TelegramBotConversationState;
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
            'name' => $data->getName()->value,
            'currency' => $data->getCurrency(),
            'countries' => $data->getCountries() === null ? null : $data->getCountries(),
        ];
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof TelegramBotPaymentMethod;
    }

    public function denormalize(mixed $data, string $type, string $format = null, array $context = []): TelegramBotPaymentMethod
    {
        return new $type(
            TelegramBotPaymentMethodName::from($data['name']),
            '***',
            $data['currency'],
            $data['countries'] ?? null,
        );
    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return is_array($data) && $type === TelegramBotPaymentMethod::class;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            TelegramBotPaymentMethod::class => false,
        ];
    }
}