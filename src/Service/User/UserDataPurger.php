<?php
declare(strict_types=1);

namespace App\Service\User;

use App\Entity\User\User;
use App\Repository\Messenger\MessengerUserRepository;
use DateTimeImmutable;

class UserDataPurger
{
    public function __construct(
        private readonly MessengerUserRepository $messengerUserRepository,
    )
    {
    }

    public function purgeUserData(User $user): void
    {
        $user
            ->setUsername(null)
            ->setName(null)
            ->setPhoneNumber(null)
            ->setEmail(null)
            ->setCountryCode(null)
            ->setLocaleCode(null)
            ->setCurrencyCode(null)
            ->setTimezone(null)
            ->setPurgedAt(new DateTimeImmutable())
        ;

        $messengerUsers = $this->messengerUserRepository->findByUser($user);

        foreach ($messengerUsers as $messengerUser) {
            $messengerUser
                ->setUsername(null)
                ->setName(null)
                ->setShowExtendedKeyboard(false)
            ;
        }
    }
}