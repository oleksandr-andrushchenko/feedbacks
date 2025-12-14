<?php

declare(strict_types=1);

namespace App\Service\Feedback;

use App\Entity\Feedback\FeedbackLookup;
use App\Exception\Feedback\FeedbackCommandLimitExceededException;
use App\Exception\ValidatorException;
use App\Factory\Feedback\FeedbackLookupFactory;
use App\Message\Event\ActivityEvent;
use App\Message\Event\Feedback\FeedbackLookupCreatedEvent;
use App\Model\Feedback\Command\FeedbackCommandOptions;
use App\Service\Feedback\Command\FeedbackCommandLimitsChecker;
use App\Service\Feedback\SearchTerm\SearchTermUpserter;
use App\Service\Feedback\Statistic\FeedbackUserStatisticProviderInterface;
use App\Service\IdGenerator;
use App\Service\Messenger\MessengerUserService;
use App\Service\ORM\EntityManager;
use App\Service\Validator\Validator;
use App\Transfer\Feedback\FeedbackLookupTransfer;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class FeedbackLookupCreator
{
    public function __construct(
        private readonly FeedbackCommandOptions $options,
        private readonly EntityManager $entityManager,
        private readonly Validator $validator,
        private readonly FeedbackUserStatisticProviderInterface $statisticProvider,
        private readonly FeedbackCommandLimitsChecker $limitsChecker,
        private readonly SearchTermUpserter $searchTermUpserter,
        private readonly IdGenerator $idGenerator,
        private readonly MessageBusInterface $eventBus,
        private readonly MessengerUserService $messengerUserService,
        private readonly FeedbackLookupFactory $feedbackLookupFactory,
    )
    {
    }

    public function getOptions(): FeedbackCommandOptions
    {
        return $this->options;
    }

    /**
     * @param FeedbackLookupTransfer $transfer
     * @return FeedbackLookup
     * @throws FeedbackCommandLimitExceededException
     * @throws ValidatorException
     * @throws ExceptionInterface
     */
    public function createFeedbackLookup(FeedbackLookupTransfer $transfer): FeedbackLookup
    {
        $this->validator->validate($transfer);

        $messengerUser = $transfer->getMessengerUser();
        $user = $this->messengerUserService->getUser($messengerUser);

        if (!$user->hasActiveSubscription()) {
            $this->limitsChecker->checkCommandLimits($user, $this->statisticProvider);
        }

        $searchTerm = $this->searchTermUpserter->upsertSearchTerm($transfer->getSearchTerm());

        $feedbackLookup = $this->feedbackLookupFactory->createFeedbackLookup(
            $this->idGenerator->generateId(), $searchTerm, $user, $messengerUser, $transfer->getTelegramBot()
        );
        $this->entityManager->persist($feedbackLookup);

        $this->eventBus->dispatch(new ActivityEvent(entity: $feedbackLookup, action: 'created'));
        $this->eventBus->dispatch(new FeedbackLookupCreatedEvent(lookup: $feedbackLookup));

        return $feedbackLookup;
    }
}
