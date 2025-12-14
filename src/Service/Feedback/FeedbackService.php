<?php

declare(strict_types=1);

namespace App\Service\Feedback;

use App\Entity\Feedback\Feedback;
use App\Entity\Feedback\SearchTerm;
use App\Entity\Messenger\MessengerUser;
use App\Entity\User\User;
use App\Repository\Feedback\FeedbackRepository;
use App\Repository\Feedback\SearchTermRepository;
use App\Repository\Messenger\MessengerUserRepository;
use App\Repository\User\UserRepository;

class FeedbackService
{
    public function __construct(
        private readonly FeedbackRepository $feedbackRepository,
        private readonly UserRepository $userRepository,
        private readonly MessengerUserRepository $messengerUserRepository,
        private readonly SearchTermRepository $searchTermRepository,
    )
    {
    }

    public function getUser(Feedback $feedback): User
    {
        $user = $feedback->getUser();

        if ($this->feedbackRepository->getConfig()->isDynamodb()) {
            if ($user !== null) {
                return $user;
            }
            $user = $this->userRepository->find($feedback->getUserId());
            $feedback->setUser($user);
        }

        return $user;
    }

    public function getMessengerUser(Feedback $feedback): MessengerUser
    {
        $messengerUser = $feedback->getMessengerUser();

        if ($this->feedbackRepository->getConfig()->isDynamodb()) {
            if ($messengerUser !== null) {
                return $messengerUser;
            }
            $messengerUser = $this->messengerUserRepository->find($feedback->getMessengerUserId());
            $feedback->setMessengerUser($messengerUser);
        }

        return $messengerUser;
    }

    /**
     * @param Feedback $feedback
     * @return array<SearchTerm>
     */
    public function getSearchTerms(Feedback $feedback): array
    {
        if ($this->feedbackRepository->getConfig()->isDynamodb()) {
            $searchTerms = $feedback->getSearchTerms();

            if (!$searchTerms->isEmpty()) {
                return $searchTerms->toArray();
            }
            $searchTerms = $this->searchTermRepository->findByIds($feedback->getSearchTermIds());
            $feedback->setSearchTerms($searchTerms);
        }

        return $feedback->getSearchTerms()->toArray();
    }
}