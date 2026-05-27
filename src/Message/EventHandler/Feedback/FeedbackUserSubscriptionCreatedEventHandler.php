<?php
declare(strict_types=1);

namespace App\Message\EventHandler\Feedback;

use App\Message\Command\Feedback\NotifyFeedbackUserSubscriptionOwnerCommand;
use App\Message\Event\ActivityEvent;
use App\Message\Event\Feedback\FeedbackUserSubscriptionCreatedEvent;
use App\Repository\Feedback\FeedbackUserSubscriptionRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class FeedbackUserSubscriptionCreatedEventHandler
{
    public function __construct(
        private readonly FeedbackUserSubscriptionRepository $feedbackUserSubscriptionRepository,
        private readonly LoggerInterface $logger,
        private readonly MessageBusInterface $eventBus,
        private readonly MessageBusInterface $commandBus,
    )
    {
    }

    public function __invoke(FeedbackUserSubscriptionCreatedEvent $event): void
    {
        $subscription = $event->getSubscription() ?? $this->feedbackUserSubscriptionRepository->find($event->getSubscriptionId());

        if ($subscription === null) {
            $this->logger->warning(sprintf('No subscription was found in %s for %s id', __CLASS__, $event->getSubscriptionId()));
            return;
        }

        $this->eventBus->dispatch(new ActivityEvent(entity: $subscription, action: 'created'));
        $this->commandBus->dispatch(new NotifyFeedbackUserSubscriptionOwnerCommand(subscription: $subscription));
    }
}