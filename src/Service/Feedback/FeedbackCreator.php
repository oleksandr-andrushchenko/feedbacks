<?php

declare(strict_types=1);

namespace App\Service\Feedback;

use App\Entity\Feedback\Feedback;
use App\Entity\Feedback\FeedbackCreatorOptions;
use App\Exception\Feedback\CreateFeedbackLimitExceeded;
use App\Exception\Messenger\SameMessengerUserException;
use App\Exception\ValidatorException;
use App\Object\Feedback\FeedbackTransfer;
use App\Service\Logger\ActivityLogger;
use App\Service\Validator;
use Doctrine\ORM\EntityManagerInterface;

class FeedbackCreator
{
    public function __construct(
        private readonly FeedbackCreatorOptions $options,
        private readonly EntityManagerInterface $entityManager,
        private readonly Validator $validator,
        private readonly UserCreateFeedbackStatisticsProvider $userStatisticsProvider,
        private readonly FeedbackSubscriptionManager $subscriptionManager,
        private readonly ActivityLogger $activityLogger,
    )
    {
    }

    public function getOptions(): FeedbackCreatorOptions
    {
        return $this->options;
    }

    /**
     * @param FeedbackTransfer $feedbackTransfer
     * @return Feedback
     * @throws CreateFeedbackLimitExceeded
     * @throws SameMessengerUserException
     * @throws ValidatorException
     */
    public function createFeedback(FeedbackTransfer $feedbackTransfer): Feedback
    {
        $this->validator->validate($feedbackTransfer);

        $this->checkSearchTermUser($feedbackTransfer);

        $messengerUser = $feedbackTransfer->getMessengerUser();
        $hasActiveSubscription = $this->subscriptionManager->hasActiveSubscription($messengerUser);

        if (!$hasActiveSubscription) {
            $this->checkLimits($feedbackTransfer);
        }

        $feedback = $this->constructFeedback($feedbackTransfer);

        $this->entityManager->persist($feedback);

        if ($this->options->logActivities()) {
            $this->activityLogger->logActivity($feedback);
        }

        return $feedback;
    }

    public function constructFeedback(FeedbackTransfer $feedbackTransfer): Feedback
    {
        $messengerUser = $feedbackTransfer->getMessengerUser();
        $hasActiveSubscription = $this->subscriptionManager->hasActiveSubscription($messengerUser);
        $searchTermTransfer = $feedbackTransfer->getSearchTerm();

        return new Feedback(
            $messengerUser->getUser(),
            $messengerUser,
            $searchTermTransfer->getText(),
            $searchTermTransfer->getNormalizedText() ?? $searchTermTransfer->getText(),
            $searchTermTransfer->getType(),
            null,
            $searchTermTransfer->getMessenger(),
            $searchTermTransfer->getMessengerUsername(),
            $feedbackTransfer->getRating(),
            $feedbackTransfer->getDescription(),
            $hasActiveSubscription,
            $messengerUser?->getUser()->getCountryCode(),
            $messengerUser?->getLocaleCode()
        );
    }

    /**
     * @param FeedbackTransfer $feedbackTransfer
     * @return void
     * @throws SameMessengerUserException
     */
    private function checkSearchTermUser(FeedbackTransfer $feedbackTransfer): void
    {
        $messengerUser = $feedbackTransfer->getMessengerUser();
        $searchTermTransfer = $feedbackTransfer->getSearchTerm();

        if (
            $messengerUser?->getUsername() !== null
            && $messengerUser?->getMessenger() !== null
            && $searchTermTransfer?->getMessengerUsername() !== null
            && strcasecmp($messengerUser->getUsername(), $searchTermTransfer->getMessengerUsername()) === 0
            && $messengerUser->getMessenger() === $searchTermTransfer?->getMessenger()
        ) {
            throw new SameMessengerUserException($messengerUser);
        }
    }

    /**
     * @param FeedbackTransfer $feedbackTransfer
     * @return void
     * @throws CreateFeedbackLimitExceeded
     */
    private function checkLimits(FeedbackTransfer $feedbackTransfer): void
    {
        $user = $feedbackTransfer->getMessengerUser()->getUser();
        $periodLimits = [
            'day' => $this->options->userPerDayLimit(),
            'month' => $this->options->userPerMonthLimit(),
            'year' => $this->options->userPerYearLimit(),
        ];
        $periodCounts = $this->userStatisticsProvider->getUserCreateFeedbackStatistics(array_keys($periodLimits), $user);

        foreach ($periodCounts as $periodKey => $periodCount) {
            if ($periodCount >= $periodLimits[$periodKey]) {
                throw new CreateFeedbackLimitExceeded($periodKey, $periodLimits[$periodKey]);
            }
        }
    }
}
