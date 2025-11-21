<?php

declare(strict_types=1);

namespace App\Serializer\Telegram\Bot;

use App\Entity\Telegram\TelegramBotPayment;
use App\Entity\Telegram\TelegramBotPaymentMethod;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class TelegramBotPaymentNormalizer implements NormalizerInterface
{
    public function __construct(
        private readonly NormalizerInterface $priceNormalizer,
    )
    {
    }

    /**
     * @param TelegramBotPayment $data
     * @param string|null $format
     * @param array $context
     * @return array
     * @throws ExceptionInterface
     */
    public function normalize(mixed $data, string $format = null, array $context = []): array
    {
        if ($format === 'activity') {
            return [
                'messenger_user' => $data->getMessengerUser()->getUsername(),
                'method' => $data->getMethod()->getName()->name,
                'purpose' => $data->getPurpose(),
                'price' => $this->priceNormalizer->normalize($data->getPrice(), $format, $context),
                'has_pre_checkout_query' => $data->getPreCheckoutQuery() !== null,
                'has_successful_payment' => $data->getSuccessfulPayment() !== null,
                'created_at' => $data->getCreatedAt()->getTimestamp(),
                'updated_at' => $data->getUpdatedAt()?->getTimestamp(),
            ];
        }

        return [];
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof TelegramBotPayment && in_array($format, ['activity'], true);
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            TelegramBotPayment::class => false,
        ];
    }
}