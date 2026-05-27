<?php
declare(strict_types=1);

namespace App\Message\EventHandler\Feedback;

use App\Message\Command\Feedback\NotifyFeedbackLookupSourcesAboutNewFeedbackLookupCommand;
use App\Message\Command\Feedback\NotifyFeedbackLookupTargetAboutNewFeedbackLookupCommand;
use App\Message\Event\Feedback\FeedbackLookupCreatedEvent;
use App\Repository\Feedback\FeedbackLookupRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class FeedbackLookupCreatedEventHandler
{
    public function __construct(
        private readonly FeedbackLookupRepository $feedbackLookupRepository,
        private readonly LoggerInterface $logger,
        private readonly MessageBusInterface $commandBus,
    )
    {
    }

    public function __invoke(FeedbackLookupCreatedEvent $event): void
    {
        $lookup = $event->getLookup() ?? $this->feedbackLookupRepository->find($event->getLookupId());

        if ($lookup === null) {
            $this->logger->warning(sprintf('No feedback lookup was found in %s for %s id', __CLASS__, $event->getLookupId()));
            return;
        }

        $this->commandBus->dispatch(new NotifyFeedbackLookupTargetAboutNewFeedbackLookupCommand(lookup: $lookup));
        $this->commandBus->dispatch(new NotifyFeedbackLookupSourcesAboutNewFeedbackLookupCommand(lookup: $lookup));
        // todo: notify FeedbackLookup Source about same searches in the past
    }
}