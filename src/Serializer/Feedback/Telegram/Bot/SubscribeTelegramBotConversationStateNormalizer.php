<?php

declare(strict_types=1);

namespace App\Serializer\Feedback\Telegram\Bot;

use App\Model\Feedback\Telegram\Bot\SubscribeTelegramBotConversationState;
use App\Model\Feedback\Telegram\FeedbackSubscriptionPlan;
use App\Repository\Telegram\Bot\TelegramBotPaymentMethodRepository;
use App\Service\Intl\CurrencyProvider;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class SubscribeTelegramBotConversationStateNormalizer implements NormalizerInterface, DenormalizerInterface
{
    public function __construct(
        private readonly NormalizerInterface $baseConversationStateNormalizer,
        private readonly DenormalizerInterface $baseConversationStateDenormalizer,
        private readonly NormalizerInterface $subscriptionPlanNormalizer,
        private readonly DenormalizerInterface $subscriptionPlanDenormalizer,
        private readonly TelegramBotPaymentMethodRepository $paymentMethodRepository,
        private readonly CurrencyProvider $currencyProvider,
    )
    {
    }

    /**
     * @param SubscribeTelegramBotConversationState $data
     * @param string|null $format
     * @param array $context
     * @return array
     * @throws ExceptionInterface
     */
    public function normalize(mixed $data, string $format = null, array $context = []): array
    {
        return array_merge($this->baseConversationStateNormalizer->normalize($data, $format, $context), [
            'currency' => $data->getCurrency() === null ? null : $data->getCurrency()->getCode(),
            'currency_step' => $data->currencyStep(),
            'subscription_plan' => $data->getSubscriptionPlan() === null ? null : $this->subscriptionPlanNormalizer->normalize($data->getSubscriptionPlan(), $format, $context),
            'payment_method' => $data->getPaymentMethod() === null ? null : $data->getPaymentMethod()->getId(),
            'payment_method_step' => $data->paymentMethodStep(),
        ]);
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof SubscribeTelegramBotConversationState;
    }

    public function denormalize(mixed $data, string $type, string $format = null, array $context = []): SubscribeTelegramBotConversationState
    {
        /** @var SubscribeTelegramBotConversationState $object */
        $object = $this->baseConversationStateDenormalizer->denormalize($data, $type, $format, $context);

        $object
            ->setCurrency(isset($data['currency']) ? $this->currencyProvider->getCurrency($data['currency']) : null)
            ->setCurrencyStep($data['currency_step'] ?? null)
            ->setSubscriptionPlan(isset($data['subscription_plan']) ? $this->subscriptionPlanDenormalizer->denormalize($data['subscription_plan'], FeedbackSubscriptionPlan::class, $format, $context) : null)
            ->setPaymentMethod(isset($data['payment_method']) ? $this->paymentMethodRepository->find($data['payment_method']) : null)
            ->setPaymentMethodStep($data['payment_method_step'] ?? null)
        ;

        return $object;
    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return is_array($data) && $type === SubscribeTelegramBotConversationState::class;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            SubscribeTelegramBotConversationState::class => false,
        ];
    }
}