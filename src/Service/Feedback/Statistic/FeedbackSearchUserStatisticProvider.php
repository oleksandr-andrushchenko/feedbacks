<?php

declare(strict_types=1);

namespace App\Service\Feedback\Statistic;

use App\Entity\User\User;
use App\Model\Feedback\Command\FeedbackCommandOptions;
use App\Repository\Feedback\FeedbackSearchRepository;
use DateTimeImmutable;
use Generator;

class FeedbackSearchUserStatisticProvider implements FeedbackUserStatisticProviderInterface
{
    public function __construct(
        private readonly FeedbackCommandOptions $feedbackCommandOptions,
        private readonly FeedbackSearchRepository $feedbackSearchRepository,
    )
    {
    }

    public function getUserStatistics(User $user): Generator
    {
        foreach ($this->getLimits() as $limit) {
            yield $limit->getPeriod() => $this->feedbackSearchRepository
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