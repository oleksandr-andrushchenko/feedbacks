<?php

declare(strict_types=1);

namespace App\Service\Feedback;

use App\Entity\Feedback\FeedbackSearch;
use App\Entity\Feedback\SearchTerm;
use App\Entity\Messenger\MessengerUser;
use App\Entity\Telegram\TelegramBot;
use App\Entity\User\User;
use App\Repository\Feedback\feedbackSearchRepository;
use App\Repository\Feedback\SearchTermRepository;
use App\Repository\Messenger\MessengerUserRepository;
use App\Repository\Telegram\Bot\TelegramBotRepository;
use App\Repository\User\UserRepository;

class FeedbackSearchService
{
    public function __construct(
        private readonly FeedbackSearchRepository $feedbackSearchRepository,
        private readonly UserRepository $userRepository,
        private readonly MessengerUserRepository $messengerUserRepository,
        private readonly SearchTermRepository $searchTermRepository,
        private readonly TelegramBotRepository $telegramBotRepository,
    )
    {
    }

    public function getUser(FeedbackSearch $feedbackSearch): User
    {
        $user = $feedbackSearch->getUser();

        if ($this->feedbackSearchRepository->getConfig()->isDynamodb()) {
            if ($user !== null) {
                return $user;
            }
            $user = $this->userRepository->find($feedbackSearch->getUserId());
            $feedbackSearch->setUser($user);
        }

        return $user;
    }

    public function getMessengerUser(FeedbackSearch $feedbackSearch): MessengerUser
    {
        $messengerUser = $feedbackSearch->getMessengerUser();

        if ($this->feedbackSearchRepository->getConfig()->isDynamodb()) {
            if ($messengerUser !== null) {
                return $messengerUser;
            }
            $messengerUser = $this->messengerUserRepository->find($feedbackSearch->getMessengerUserId());
            $feedbackSearch->setMessengerUser($messengerUser);
        }

        return $messengerUser;
    }

    public function getSearchTerm(FeedbackSearch $feedbackSearch): SearchTerm
    {
        $searchTerm = $feedbackSearch->getSearchTerm();

        if ($this->feedbackSearchRepository->getConfig()->isDynamodb()) {
            if ($searchTerm !== null) {
                return $searchTerm;
            }
            $searchTerm = $this->searchTermRepository->find($feedbackSearch->getSearchTermId());
            $feedbackSearch->setSearchTerm($searchTerm);
        }

        return $searchTerm;
    }

    public function getTelegramBot(FeedbackSearch $feedbackSearch): ?TelegramBot
    {
        $telegramBot = $feedbackSearch->getTelegramBot();

        if ($this->feedbackSearchRepository->getConfig()->isDynamodb()) {
            if ($telegramBot !== null) {
                return $telegramBot;
            }
            if ($feedbackSearch->getTelegramBotId() !== null) {
                $telegramBot = $this->telegramBotRepository->find($feedbackSearch->getTelegramBotId());
                $feedbackSearch->setTelegramBot($telegramBot);
            }
        }

        return $telegramBot;
    }
}