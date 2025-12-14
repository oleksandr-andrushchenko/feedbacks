<?php

declare(strict_types=1);

namespace App\Service\User;

use App\Entity\Messenger\MessengerUser;
use App\Entity\User\User;
use App\Service\IdGenerator;
use App\Service\Messenger\MessengerUserService;
use App\Service\ORM\EntityManager;
use App\Transfer\Messenger\MessengerUserTransfer;

class UserUpserter
{
    public function __construct(
        private readonly EntityManager $entityManager,
        private readonly IdGenerator $idGenerator,
        private readonly MessengerUserService $messengerUserService,
    )
    {
    }

    public function upsertUserByMessengerUser(MessengerUser $messengerUser, MessengerUserTransfer $transfer): User
    {
        $user = $this->messengerUserService->getUser($messengerUser);

        if ($user === null) {
            $user = new User(
                $this->idGenerator->generateId()
            );
            $this->entityManager->persist($user);

            $messengerUser->setUser($user);
        }

        if (empty($user->getUsername()) && !empty($messengerUser->getUsername())) {
            $user->setUsername($messengerUser->getUsername());
        }
        if (empty($user->getName()) && !empty($messengerUser->getName())) {
            $user->setName($messengerUser->getName());
        }
        if (empty($user->getCountryCode()) && !empty($transfer->getCountryCode())) {
            $user->setCountryCode($transfer->getCountryCode());
        }
        if ($user->getLocaleCode() === null && $transfer->getLocaleCode() !== null) {
            $user->setLocaleCode($transfer->getLocaleCode());
        }
        if (empty($user->getCurrencyCode()) && !empty($transfer->getCurrencyCode())) {
            $user->setCurrencyCode($transfer->getCurrencyCode());
        }
        if (empty($user->getTimezone()) && !empty($transfer->getTimezone())) {
            $user->setTimezone($transfer->getTimezone());
        }

        return $user;
    }
}