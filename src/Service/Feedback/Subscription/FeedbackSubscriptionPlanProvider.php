<?php

declare(strict_types=1);

namespace App\Service\Feedback\Subscription;

use App\Enum\Feedback\FeedbackSubscriptionPlanName;
use App\Model\Feedback\Telegram\FeedbackSubscriptionPlan;
use Symfony\Contracts\Translation\TranslatorInterface;

class FeedbackSubscriptionPlanProvider
{
    public function __construct(
        private readonly array $sourceSubscriptionPlans,
        private readonly TranslatorInterface $translator,
    )
    {
    }

    /**
     * @param string|null $country
     * @return FeedbackSubscriptionPlan[]
     */
    public function getSubscriptionPlans(string $country = null): array
    {
        static $subscriptionPlans = null;

        if ($subscriptionPlans === null) {
            $subscriptionPlans = [];

            foreach ($this->sourceSubscriptionPlans as $subscriptionPlanName => $subscriptionPlan) {
                $subscriptionPlans[] = new FeedbackSubscriptionPlan(
                    FeedbackSubscriptionPlanName::fromName($subscriptionPlanName),
                    $subscriptionPlan['duration'],
                    $subscriptionPlan['default_price'],
                    $subscriptionPlan['prices'] ?? [],
                    $subscriptionPlan['countries'] ?? [],
                );
            }
        }

        if ($country !== null) {
            $subscriptionPlans = array_filter(
                $subscriptionPlans,
                static fn (FeedbackSubscriptionPlan $subscriptionPlan): bool => $subscriptionPlan->isGlobal() || in_array($country, $subscriptionPlan->getCountries(), true)
            );
        }

        return $subscriptionPlans;
    }

    public function getSubscriptionPlan(FeedbackSubscriptionPlanName $subscriptionPlanName): ?FeedbackSubscriptionPlan
    {
        foreach ($this->getSubscriptionPlans() as $subscriptionPlan) {
            if ($subscriptionPlan->getName() === $subscriptionPlanName) {
                return $subscriptionPlan;
            }
        }

        return null;
    }

    public function getSubscriptionPlanName(FeedbackSubscriptionPlanName $plan, string $localeCode = null): string
    {
        return $this->translator->trans($plan->name, domain: 'feedbacks.subscription_plan', locale: $localeCode);
    }
}