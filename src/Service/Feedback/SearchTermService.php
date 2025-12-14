<?php

declare(strict_types=1);

namespace App\Service\Feedback;

use App\Entity\Feedback\SearchTerm;
use App\Entity\Messenger\MessengerUser;
use App\Repository\Messenger\MessengerUserRepository;

class SearchTermService
{
    public function __construct(
        private MessengerUserRepository $messengerUserRepository,
    )
    {
    }

    public function getMessengerUser(?SearchTerm $feedbackSearchTerm): ?MessengerUser
    {
        if ($this->messengerUserRepository->getConfig()->isDynamodb()) {
            $messengerUserId = $feedbackSearchTerm?->getMessengerUserId();
            if ($messengerUserId === null) {
                return null;
            }
            $messengerUser = $feedbackSearchTerm->getMessengerUser();
            if ($messengerUser !== null) {
                return $messengerUser;
            }
            $messengerUser = $this->messengerUserRepository->getDynamodb()->find($messengerUserId);
            $feedbackSearchTerm->setMessengerUser($messengerUser);
            return $messengerUser;
        }

        return $feedbackSearchTerm?->getMessengerUser();
    }
}