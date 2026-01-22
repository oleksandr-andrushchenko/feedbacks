<?php

declare(strict_types=1);

namespace App\Model\Feedback\Telegram\Bot;

use App\Entity\Telegram\TelegramBotPaymentMethod;
use App\Model\Feedback\Telegram\FeedbackSubscriptionPlan;
use App\Model\Intl\Currency;
use App\Model\Telegram\TelegramBotConversationState;

class SubscribeTelegramBotConversationState extends TelegramBotConversationState
{
    public function __construct(
        ?int $step = null,
        private ?bool $currencyStep = null,
        private ?bool $paymentMethodStep = null,
        private ?FeedbackSubscriptionPlan $subscriptionPlan = null,
        private ?TelegramBotPaymentMethod $paymentMethod = null,
        private ?Currency $currency = null,
    )
    {
        parent::__construct($step);
    }

    public function getSubscriptionPlan(): ?FeedbackSubscriptionPlan
    {
        return $this->subscriptionPlan;
    }

    public function setSubscriptionPlan(?FeedbackSubscriptionPlan $subscriptionPlan): static
    {
        $this->subscriptionPlan = $subscriptionPlan;

        return $this;
    }

    public function getPaymentMethod(): ?TelegramBotPaymentMethod
    {
        return $this->paymentMethod;
    }

    public function setPaymentMethod(?TelegramBotPaymentMethod $paymentMethod): static
    {
        $this->paymentMethod = $paymentMethod;

        return $this;
    }

    public function paymentMethodStep(): ?bool
    {
        return $this->paymentMethodStep;
    }

    public function setPaymentMethodStep(?bool $paymentMethodStep): static
    {
        $this->paymentMethodStep = $paymentMethodStep;

        return $this;
    }

    public function currencyStep(): ?bool
    {
        return $this->currencyStep;
    }

    public function setCurrencyStep(?bool $currencyStep): static
    {
        $this->currencyStep = $currencyStep;

        return $this;
    }

    public function getCurrency(): ?Currency
    {
        return $this->currency;
    }

    public function setCurrency(?Currency $currency): static
    {
        $this->currency = $currency;

        return $this;
    }
}
