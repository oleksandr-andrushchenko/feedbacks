<?php

declare(strict_types=1);

namespace App\Service\Feedback;

use App\Entity\Feedback\FeedbackLookup;
use App\Entity\Feedback\SearchTerm;
use App\Entity\Messenger\MessengerUser;
use App\Entity\Telegram\TelegramBot;
use App\Entity\User\User;
use App\Repository\Feedback\FeedbackLookupRepository;
use App\Repository\Feedback\SearchTermRepository;
use App\Repository\Messenger\MessengerUserRepository;
use App\Repository\Telegram\Bot\TelegramBotRepository;
use App\Repository\User\UserRepository;

class FeedbackLookupService
{
    public function __construct(
        private readonly FeedbackLookupRepository $feedbackLookupRepository,
        private readonly UserRepository $userRepository,
        private readonly MessengerUserRepository $messengerUserRepository,
        private readonly SearchTermRepository $searchTermRepository,
        private readonly TelegramBotRepository $telegramBotRepository,
    )
    {
    }

    public function getUser(FeedbackLookup $feedbackLookup): User
    {
        $user = $feedbackLookup->getUser();

        if ($this->feedbackLookupRepository->getConfig()->isDynamodb()) {
            if ($user !== null) {
                return $user;
            }
            $user = $this->userRepository->find($feedbackLookup->getUserId());
            $feedbackLookup->setUser($user);
        }

        return $user;
    }

    public function getMessengerUser(FeedbackLookup $feedbackLookup): MessengerUser
    {
        $messengerUser = $feedbackLookup->getMessengerUser();

        if ($this->feedbackLookupRepository->getConfig()->isDynamodb()) {
            if ($messengerUser !== null) {
                return $messengerUser;
            }
            $messengerUser = $this->messengerUserRepository->find($feedbackLookup->getMessengerUserId());
            $feedbackLookup->setMessengerUser($messengerUser);
        }

        return $messengerUser;
    }

    public function getSearchTerm(FeedbackLookup $feedbackLookup): SearchTerm
    {
        $searchTerm = $feedbackLookup->getSearchTerm();

        if ($this->feedbackLookupRepository->getConfig()->isDynamodb()) {
            if ($searchTerm !== null) {
                return $searchTerm;
            }
            $searchTerm = $this->searchTermRepository->find($feedbackLookup->getSearchTermId());
            $feedbackLookup->setSearchTerm($searchTerm);
        }

        return $searchTerm;
    }

    public function getTelegramBot(FeedbackLookup $feedbackLookup): ?TelegramBot
    {
        $telegramBot = $feedbackLookup->getTelegramBot();

        if ($this->feedbackLookupRepository->getConfig()->isDynamodb()) {
            if ($telegramBot !== null) {
                return $telegramBot;
            }
            if ($feedbackLookup->getTelegramBotId() !== null) {
                $telegramBot = $this->telegramBotRepository->find($feedbackLookup->getTelegramBotId());
                $feedbackLookup->setTelegramBot($telegramBot);
            }
        }

        return $telegramBot;
    }
}