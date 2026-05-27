<?php
declare(strict_types=1);

namespace App\Tests\Traits\Feedback;

use App\Service\Feedback\Subscription\FeedbackSubscriptionPlanProvider;

trait FeedbackSubscriptionPlanProviderTrait
{
    public function getFeedbackSubscriptionPlanProvider(): FeedbackSubscriptionPlanProvider
    {
        return static::getContainer()->get('app.feedback_subscription_plan_provider');
    }
}