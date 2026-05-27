<?php
declare(strict_types=1);

namespace App\Service\Feedback\Command;

use App\Entity\User\User;
use App\Exception\Feedback\FeedbackCommandLimitExceededException;
use App\Service\Feedback\Statistic\FeedbackUserStatisticProviderInterface;

class FeedbackCommandLimitsChecker
{
    public function __construct(
        private readonly bool $checkLimits,
    )
    {
    }

    /**
     * @param User $user
     * @param FeedbackUserStatisticProviderInterface $statisticProvider
     * @return void
     * @throws FeedbackCommandLimitExceededException
     */
    public function checkCommandLimits(User $user, FeedbackUserStatisticProviderInterface $statisticProvider): void
    {
        if (!$this->checkLimits) {
            return;
        }

        $statistics = $statisticProvider->getUserStatistics($user);
        $limits = $statisticProvider->getLimits();

        foreach ($statistics as $period => $current) {
            $count = null;

            foreach ($limits as $limit) {
                if ($limit->getPeriod() === $period) {
                    $count = $limit->getCount();
                    break;
                }
            }

            if ($count === null) {
                continue;
            }

            if ($current >= $count) {
                throw new FeedbackCommandLimitExceededException($limit);
            }
        }
    }
}