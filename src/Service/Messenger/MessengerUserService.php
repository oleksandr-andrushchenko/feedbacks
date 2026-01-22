<?php

declare(strict_types=1);

namespace App\Service\Messenger;

use App\Entity\Messenger\MessengerUser;
use App\Entity\User\User;
use App\Repository\User\UserRepository;

class MessengerUserService
{
    public function __construct(
        private readonly UserRepository $userRepository,
    )
    {
    }

    /**
     * @param MessengerUser|null $messengerUser
     * @return User|null
     */
    public function getUser(?MessengerUser $messengerUser): ?User
    {
        if ($this->userRepository->getConfig()->isDynamodb()) {
            $userId = $messengerUser?->getUserId();
            if ($userId === null) {
                return null;
            }
            $user = $messengerUser->getUser();
            if ($user !== null) {
                return $user;
            }
            $user = $this->userRepository->getDynamodb()->find($userId);
            $messengerUser->setUser($user);
            return $user;
        }

        return $messengerUser?->getUser();
    }
}