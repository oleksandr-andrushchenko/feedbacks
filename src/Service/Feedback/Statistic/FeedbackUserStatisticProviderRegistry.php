<?php
declare(strict_types=1);

namespace App\Service\Feedback\Statistic;

use Symfony\Component\DependencyInjection\ServiceLocator;

class FeedbackUserStatisticProviderRegistry
{
    public function __construct(
        private readonly ServiceLocator $serviceLocator,
    )
    {
    }

    public function __invoke(string $class): FeedbackUserStatisticProviderInterface
    {
        return $this->serviceLocator->get($class);
    }
}