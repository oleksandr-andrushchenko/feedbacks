<?php

declare(strict_types=1);

namespace App\Service\Feedback\Notify;

use Symfony\Component\DependencyInjection\ServiceLocator;

class FeedbackSearchesNotifierRegistry
{
    public function __construct(
        private readonly ServiceLocator $serviceLocator,
    )
    {
    }

    public function getFeedbackSearchUserNotifier(string $id): FeedbackSearchesNotifierInterface
    {
        return $this->serviceLocator->get($id);
    }

    /**
     * @return FeedbackSearchesNotifierInterface[]
     */
    public function getFeedbackSearchUserNotifiers(): iterable
    {
        foreach ($this->serviceLocator->getProvidedServices() as $name => $service) {
            yield $name => $this->getFeedbackSearchUserNotifier($name);
        }
    }
}