<?php

declare(strict_types=1);

namespace App\Service\Feedback\Subscription;

use App\Entity\Feedback\FeedbackUserSubscription;
use App\Entity\Messenger\MessengerUser;
use App\Entity\Telegram\TelegramBotPayment;
use App\Entity\User\User;
use App\Enum\Feedback\FeedbackSubscriptionPlanName;
use App\Message\Event\Feedback\FeedbackUserSubscriptionCreatedEvent;
use App\Repository\Feedback\FeedbackUserSubscriptionRepository;
use App\Service\IdGenerator;
use App\Service\Messenger\MessengerUserService;
use App\Service\ORM\EntityManager;
use App\Service\Telegram\TelegramBotPaymentService;
use DateTimeImmutable;
use Symfony\Component\Messenger\MessageBusInterface;

class FeedbackSubscriptionManager
{
    public function __construct(
        private readonly FeedbackSubscriptionPlanProvider $feedbackSubscriptionPlanProvider,
        private readonly FeedbackUserSubscriptionRepository $feedbackUserSubscriptionRepository,
        private readonly EntityManager $entityManager,
        private readonly IdGenerator $idGenerator,
        private readonly MessageBusInterface $eventBus,
        private readonly MessengerUserService $messengerUserService,
        private readonly TelegramBotPaymentService $telegramBotPaymentService,
    )
    {
    }

    public function createFeedbackUserSubscriptionByTelegramPayment(TelegramBotPayment $payment): FeedbackUserSubscription
    {
        $messengerUser = $this->telegramBotPaymentService->getMessengerUser($payment);
        $user = $this->messengerUserService->getUser($messengerUser);

        return $this->createFeedbackUserSubscription(
            $user,
            FeedbackSubscriptionPlanName::fromName($payment->getPurpose()),
            $messengerUser,
            $payment
        );
    }

    public function createFeedbackUserSubscription(
        User $user,
        FeedbackSubscriptionPlanName $planName,
        MessengerUser $messengerUser = null,
        TelegramBotPayment $telegramPayment = null
    ): FeedbackUserSubscription
    {
        $subscriptionPlan = $this->feedbackSubscriptionPlanProvider->getSubscriptionPlan($planName);

        $subscription = new FeedbackUserSubscription(
            $this->idGenerator->generateId(),
            $user,
            $subscriptionPlan->getName(),
            (new DateTimeImmutable())->modify($subscriptionPlan->getDatetimeModifier()),
            $messengerUser,
            $telegramPayment
        );
        $this->entityManager->persist($subscription);

        $this->eventBus->dispatch(new FeedbackUserSubscriptionCreatedEvent(subscription: $subscription));

        $user->setSubscriptionExpireAt($subscription->getExpireAt());

        return $subscription;
    }

    /**
     * @param MessengerUser $messengerUser
     * @return FeedbackUserSubscription[]
     */
    public function getSubscriptions(MessengerUser $messengerUser): array
    {
        $user = $this->messengerUserService->getUser($messengerUser);

        if ($user === null) {
            return $this->feedbackUserSubscriptionRepository->findByMessengerUser($messengerUser);
        }

        return $this->feedbackUserSubscriptionRepository->findByUser($user);
    }

    public function getActiveSubscription(MessengerUser $messengerUser): ?FeedbackUserSubscription
    {
        $subscriptions = $this->getSubscriptions($messengerUser);

        if (count($subscriptions) === 0) {
            return null;
        }

        foreach ($subscriptions as $subscription) {
            if ($this->isSubscriptionActive($subscription)) {
                return $subscription;
            }
        }

        return null;
    }

    public function isSubscriptionActive(FeedbackUserSubscription $subscription): bool
    {
        return new DateTimeImmutable() < $subscription->getExpireAt();
    }

    public function hasActiveSubscription(MessengerUser $messengerUser): bool
    {
        $user = $this->messengerUserService->getUser($messengerUser);
        if ($user?->getSubscriptionExpireAt() === null) {
            return false;
        }

        return new DateTimeImmutable() < $user->getSubscriptionExpireAt();
    }

    public function hasSubscription(MessengerUser $messengerUser): bool
    {
        $user = $this->messengerUserService->getUser($messengerUser);
        return $user?->getSubscriptionExpireAt() !== null;
    }
}