<?php

declare(strict_types=1);

namespace App\Service\Feedback;

use App\Entity\Feedback\Feedback;
use App\Exception\Feedback\FeedbackCommandLimitExceededException;
use App\Exception\Feedback\FeedbackOnOneselfException;
use App\Exception\ValidatorException;
use App\Factory\Feedback\FeedbackFactory;
use App\Factory\Feedback\SearchTermFeedbackFactory;
use App\Message\Event\ActivityEvent;
use App\Message\Event\Feedback\FeedbackCreatedEvent;
use App\Model\Feedback\Command\FeedbackCommandOptions;
use App\Service\Feedback\Command\FeedbackCommandLimitsChecker;
use App\Service\Feedback\SearchTerm\SearchTermMessengerProvider;
use App\Service\Feedback\SearchTerm\SearchTermUpserter;
use App\Service\Feedback\Statistic\FeedbackUserStatisticProviderInterface;
use App\Service\Messenger\MessengerUserService;
use App\Service\ORM\EntityManager;
use App\Service\Validator\Validator;
use App\Transfer\Feedback\FeedbackTransfer;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class FeedbackCreator
{
    public function __construct(
        private readonly FeedbackCommandOptions $feedbackCommandOptions,
        private readonly EntityManager $entityManager,
        private readonly Validator $validator,
        private readonly FeedbackUserStatisticProviderInterface $feedbackCommandStatisticProvider,
        private readonly FeedbackCommandLimitsChecker $feedbackCommandLimitsChecker,
        private readonly SearchTermUpserter $searchTermUpserter,
        private readonly MessageBusInterface $eventBus,
        private readonly SearchTermMessengerProvider $searchTermMessengerProvider,
        private readonly MessengerUserService $messengerUserService,
        private readonly SearchTermFeedbackFactory $searchTermFeedbackFactory,
        private readonly FeedbackFactory $feedbackFactory,
    )
    {
    }

    public function getOptions(): FeedbackCommandOptions
    {
        return $this->feedbackCommandOptions;
    }

    /**
     * @param FeedbackTransfer $transfer
     * @return Feedback
     * @throws FeedbackCommandLimitExceededException
     * @throws FeedbackOnOneselfException
     * @throws ValidatorException
     * @throws ExceptionInterface
     */
    public function createFeedback(FeedbackTransfer $transfer): Feedback
    {
        $this->validator->validate($transfer);

        $this->checkSearchTermUser($transfer);

        $messengerUser = $transfer->getMessengerUser();
        $user = $this->messengerUserService->getUser($messengerUser);

        if (!$user->hasActiveSubscription()) {
            $this->feedbackCommandLimitsChecker->checkCommandLimits($user, $this->feedbackCommandStatisticProvider);
        }

        $feedback = $this->constructFeedback($transfer);
        $this->entityManager->persist($feedback);

        if ($this->entityManager->getConfig()->isDynamodb()) {
            $searchTerms = $feedback->getSearchTerms()->toArray();
            foreach ($searchTerms as $searchTerm) {
                $extraSearchTerms = array_values(array_filter($searchTerms, static fn ($otherSearchTerm) => $otherSearchTerm->getId() !== $searchTerm->getId()));
                $searchTermFeedback = $this->searchTermFeedbackFactory->createSearchTermFeedback($searchTerm, $feedback, empty($extraSearchTerms) ? null : $extraSearchTerms);
                $this->entityManager->persist($searchTermFeedback);
            }
        }

        $this->eventBus->dispatch(new ActivityEvent(entity: $feedback, action: 'created'));
        $this->eventBus->dispatch(new FeedbackCreatedEvent(feedback: $feedback));

        return $feedback;
    }

    public function constructFeedback(FeedbackTransfer $transfer): Feedback
    {
        $messengerUser = $transfer->getMessengerUser();
        $user = $this->messengerUserService->getUser($messengerUser);

        $searchTerms = [];
        foreach ($transfer->getSearchTerms()->getItemsAsArray() as $searchTerm) {
            $searchTerms[] = $this->searchTermUpserter->upsertSearchTerm($searchTerm);
        }

        return $this->feedbackFactory->createFeedback(
            $user,
            $messengerUser,
            $searchTerms,
            $transfer->getRating(),
            $transfer->getDescription(),
            $transfer->getTelegramBot()
        );
    }

    /**
     * @param FeedbackTransfer $transfer
     * @return void
     * @throws FeedbackOnOneselfException
     */
    private function checkSearchTermUser(FeedbackTransfer $transfer): void
    {
        $messengerUser = $transfer->getMessengerUser();

        foreach ($transfer->getSearchTerms()->getItemsAsArray() as $searchTerm) {
            $messenger = $this->searchTermMessengerProvider->getSearchTermMessenger($searchTerm->getType());

            if (
                $messengerUser?->getUsername() !== null
                && $messengerUser?->getMessenger() !== null
                && strcmp(mb_strtolower($messengerUser->getUsername()), mb_strtolower($searchTerm->getNormalizedText() ?? $searchTerm->getText())) === 0
                && $messengerUser->getMessenger() === $messenger
            ) {
                throw new FeedbackOnOneselfException($messengerUser);
            }
        }
    }
}
