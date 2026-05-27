<?php
declare(strict_types=1);

namespace App\Transfer\Feedback;

use App\Entity\Messenger\MessengerUser;
use App\Entity\Telegram\TelegramBot;

readonly class FeedbackSearchTransfer
{
    public function __construct(
        private ?MessengerUser $messengerUser,
        private ?SearchTermTransfer $searchTerm,
        private ?TelegramBot $telegramBot,
    )
    {
    }

    public function getMessengerUser(): ?MessengerUser
    {
        return $this->messengerUser;
    }

    public function getSearchTerm(): ?SearchTermTransfer
    {
        return $this->searchTerm;
    }

    public function getTelegramBot(): ?TelegramBot
    {
        return $this->telegramBot;
    }
}