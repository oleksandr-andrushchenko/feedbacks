<?php

declare(strict_types=1);

namespace App\Service\Feedback;

use App\Entity\Feedback\FeedbackSearch;
use App\Exception\Feedback\FeedbackCommandLimitExceededException;
use App\Exception\ValidatorException;
use App\Factory\Feedback\FeedbackSearchFactory;
use App\Factory\Feedback\SearchTermFeedbackSearchFactory;
use App\Message\Event\ActivityEvent;
use App\Message\Event\Feedback\FeedbackSearchCreatedEvent;
use App\Model\Feedback\Command\FeedbackCommandOptions;
use App\Service\Feedback\Command\FeedbackCommandLimitsChecker;
use App\Service\Feedback\SearchTerm\SearchTermUpserter;
use App\Service\Feedback\Statistic\FeedbackUserStatisticProviderInterface;
use App\Service\Messenger\MessengerUserService;
use App\Service\ORM\EntityManager;
use App\Service\Validator\Validator;
use App\Transfer\Feedback\FeedbackSearchTransfer;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class FeedbackSearchCreator
{
    public function __construct(
        private readonly FeedbackCommandOptions $options,
        private readonly EntityManager $entityManager,
        private readonly Validator $validator,
        private readonly FeedbackUserStatisticProviderInterface $statisticProvider,
        private readonly FeedbackCommandLimitsChecker $limitsChecker,
        private readonly SearchTermUpserter $searchTermUpserter,
        private readonly MessageBusInterface $eventBus,
        private readonly MessengerUserService $messengerUserService,
        private readonly SearchTermFeedbackSearchFactory $searchTermFeedbackSearchFactory,
        private readonly FeedbackSearchFactory $feedbackSearchFactory,
    )
    {
    }

    public function getOptions(): FeedbackCommandOptions
    {
        return $this->options;
    }

    /**
     * @param FeedbackSearchTransfer $transfer
     * @return FeedbackSearch
     * @throws FeedbackCommandLimitExceededException
     * @throws ValidatorException
     * @throws ExceptionInterface
     */
    public function createFeedbackSearch(FeedbackSearchTransfer $transfer): FeedbackSearch
    {
        $this->validator->validate($transfer);

        $messengerUser = $transfer->getMessengerUser();
        $user = $this->messengerUserService->getUser($messengerUser);

//        if (!$user->hasActiveSubscription()) {
//            $this->limitsChecker->checkCommandLimits($user, $this->statisticProvider);
//        }

        $searchTerm = $this->searchTermUpserter->upsertSearchTerm($transfer->getSearchTerm());

        $feedbackSearch = $this->feedbackSearchFactory->createFeedbackSearch(
            $searchTerm,
            $user,
            $messengerUser,
            $transfer->getTelegramBot()
        );
        $this->entityManager->persist($feedbackSearch);

        if ($this->entityManager->getConfig()->isDynamodb()) {
            $searchTermFeedbackSearch = $this->searchTermFeedbackSearchFactory->createSearchTermFeedbackSearch($searchTerm, $feedbackSearch);
            $this->entityManager->persist($searchTermFeedbackSearch);
        }

        $this->eventBus->dispatch(new ActivityEvent(entity: $feedbackSearch, action: 'created'));
        $this->eventBus->dispatch(new FeedbackSearchCreatedEvent(search: $feedbackSearch));

        return $feedbackSearch;
    }
}
