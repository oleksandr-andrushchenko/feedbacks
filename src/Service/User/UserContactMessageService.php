<?php

declare(strict_types=1);

namespace App\Service\User;

use App\Entity\Messenger\MessengerUser;
use App\Entity\Telegram\TelegramBot;
use App\Entity\User\User;
use App\Entity\User\UserContactMessage;
use App\Repository\Messenger\MessengerUserRepository;
use App\Repository\Telegram\Bot\TelegramBotRepository;
use App\Repository\User\UserContactMessageRepository;
use App\Repository\User\UserRepository;

class UserContactMessageService
{
    public function __construct(
        private readonly UserContactMessageRepository $userContactMessageRepository,
        private readonly UserRepository $userRepository,
        private readonly MessengerUserRepository $messengerUserRepository,
        private readonly TelegramBotRepository $telegramBotRepository,
    )
    {
    }

    public function getUser(UserContactMessage $userContactMessage): User
    {
        $user = $userContactMessage->getUser();

        if ($this->userContactMessageRepository->getConfig()->isDynamodb()) {
            if ($user !== null) {
                return $user;
            }
            $user = $this->userRepository->find($userContactMessage->getUserId());
            $userContactMessage->setUser($user);
        }

        return $user;
    }

    public function getMessengerUser(UserContactMessage $userContactMessage): MessengerUser
    {
        $messengerUser = $userContactMessage->getMessengerUser();

        if ($this->userContactMessageRepository->getConfig()->isDynamodb()) {
            if ($messengerUser !== null) {
                return $messengerUser;
            }
            $messengerUser = $this->messengerUserRepository->find($userContactMessage->getMessengerUserId());
            $userContactMessage->setMessengerUser($messengerUser);
        }

        return $messengerUser;
    }

    public function getTelegramBot(UserContactMessage $userContactMessage): ?TelegramBot
    {
        $telegramBot = $userContactMessage->getTelegramBot();

        if ($this->userContactMessageRepository->getConfig()->isDynamodb()) {
            if ($telegramBot !== null) {
                return $telegramBot;
            }
            if ($userContactMessage->getTelegramBotId() !== null) {
                $telegramBot = $this->telegramBotRepository->find($userContactMessage->getTelegramBotId());
                $userContactMessage->setTelegramBot($telegramBot);
            }
        }

        return $telegramBot;
    }
}