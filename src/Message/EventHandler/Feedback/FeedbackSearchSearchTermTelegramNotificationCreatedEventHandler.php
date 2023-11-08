<?php

declare(strict_types=1);

namespace App\Message\EventHandler\Feedback;

use App\Message\Command\NotifyAdminAboutNewActivityCommand;
use App\Message\Event\Feedback\FeedbackSearchSearchTermTelegramNotificationCreatedEvent;
use App\Repository\Feedback\FeedbackSearchSearchTermTelegramNotificationRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class FeedbackSearchSearchTermTelegramNotificationCreatedEventHandler
{
    public function __construct(
        private readonly FeedbackSearchSearchTermTelegramNotificationRepository $feedbackSearchSearchTermUserTelegramNotificationRepository,
        private readonly LoggerInterface $logger,
        private readonly MessageBusInterface $commandBus,
    )
    {
    }

    public function __invoke(FeedbackSearchSearchTermTelegramNotificationCreatedEvent $event): void
    {
        $notification = $event->getNotification() ?? $this->feedbackSearchSearchTermUserTelegramNotificationRepository->find($event->getNotificationId());

        if ($notification === null) {
            $this->logger->warning(sprintf('No notification was found in %s for %s id', __CLASS__, $event->getNotificationId()));
            return;
        }

        $this->commandBus->dispatch(new NotifyAdminAboutNewActivityCommand(entity: $notification));
    }
}