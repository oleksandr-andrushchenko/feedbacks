<?php

declare(strict_types=1);

namespace App\Service\Feedback\Command;

use App\Model\Feedback\Command\FeedbackCommandLimit;
use App\Model\Feedback\Command\FeedbackCommandOptions;

class FeedbackCommandOptionsFactory
{
    public function __invoke(array $options): FeedbackCommandOptions
    {
        $limits = [];
        foreach ($options['limits'] as $period => $count) {
            $limits[] = new FeedbackCommandLimit($period, $count);
        }

        return new FeedbackCommandOptions(
            $limits,
        );
    }
}