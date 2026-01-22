<?php

declare(strict_types=1);

namespace App\Service\Feedback\Statistic;

use App\Entity\User\User;
use App\Model\Feedback\Command\FeedbackCommandOptions;
use App\Repository\Feedback\FeedbackRepository;
use DateTimeImmutable;
use Generator;

class FeedbackUserStatisticProvider implements FeedbackUserStatisticProviderInterface
{
    public function __construct(
        private readonly FeedbackCommandOptions $feedbackCommandOptions,
        private readonly FeedbackRepository $feedbackRepository,
    )
    {
    }

    public function getUserStatistics(User $user): Generator
    {
        foreach ($this->getLimits() as $limit) {
            yield $limit->getPeriod() => $this->feedbackRepository
                ->countByUserAndFromWithoutActiveSubscription(
                    $user,
                    new DateTimeImmutable(sprintf('-1 %s', $limit->getPeriod()))
                )
            ;
        }
    }

    public function getLimits(): array
    {
        return $this->feedbackCommandOptions->getLimits();
    }
}