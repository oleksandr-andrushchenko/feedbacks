<?php

declare(strict_types=1);

namespace App\Service\Feedback\Statistic;

use App\Entity\User\User;
use App\Model\Feedback\Command\FeedbackCommandLimit;
use Generator;

interface FeedbackUserStatisticProviderInterface
{
    public function getUserStatistics(User $user): Generator;

    /**
     * @return FeedbackCommandLimit[]
     */
    public function getLimits(): array;
}