<?php

declare(strict_types=1);

namespace App\Service\Feedback;

use App\Entity\CommandOptions;
use App\Entity\Feedback\FeedbackSearch;
use App\Exception\CommandLimitExceeded;
use App\Exception\ValidatorException;
use App\Object\Feedback\FeedbackSearchTransfer;
use App\Service\Command\CommandLimitsChecker;
use App\Service\Command\CommandStatisticsProviderInterface;
use App\Service\Logger\ActivityLogger;
use App\Service\Validator;
use Doctrine\ORM\EntityManagerInterface;

class FeedbackSearchCreator
{
    public function __construct(
        private readonly CommandOptions $options,
        private readonly EntityManagerInterface $entityManager,
        private readonly Validator $validator,
        private readonly CommandStatisticsProviderInterface $statisticsProvider,
        private readonly CommandLimitsChecker $limitsChecker,
        private readonly FeedbackSubscriptionManager $subscriptionManager,
        private readonly ActivityLogger $activityLogger,
    )
    {
    }

    public function getOptions(): CommandOptions
    {
        return $this->options;
    }

    /**
     * @param FeedbackSearchTransfer $feedbackSearchTransfer
     * @return FeedbackSearch
     * @throws CommandLimitExceeded
     * @throws ValidatorException
     */
    public function createFeedbackSearch(FeedbackSearchTransfer $feedbackSearchTransfer): FeedbackSearch
    {
        $this->validator->validate($feedbackSearchTransfer);

        $messengerUser = $feedbackSearchTransfer->getMessengerUser();
        $hasActiveSubscription = $this->subscriptionManager->hasActiveSubscription($messengerUser);

        if (!$hasActiveSubscription) {
            $this->limitsChecker->checkCommandLimits($messengerUser->getUser(), $this->statisticsProvider);
        }

        $searchTermTransfer = $feedbackSearchTransfer->getSearchTerm();
        $searchTermMessengerUser = null;

        $feedbackSearch = new FeedbackSearch(
            $messengerUser->getUser(),
            $messengerUser,
            $searchTermTransfer->getText(),
            $searchTermTransfer->getNormalizedText() ?? $searchTermTransfer->getText(),
            $searchTermTransfer->getType(),
            $searchTermMessengerUser,
            $searchTermTransfer->getMessenger(),
            $searchTermTransfer->getMessengerUsername(),
            $hasActiveSubscription,
            $messengerUser->getUser()->getCountryCode(),
            $messengerUser->getUser()->getLocaleCode()
        );
        $this->entityManager->persist($feedbackSearch);

        if ($this->options->shouldLogActivities()) {
            $this->activityLogger->logActivity($feedbackSearch);
        }

        return $feedbackSearch;
    }
}
