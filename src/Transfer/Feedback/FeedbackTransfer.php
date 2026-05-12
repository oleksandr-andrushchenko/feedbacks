<?php

declare(strict_types=1);

namespace App\Transfer\Feedback;

use App\Entity\Messenger\MessengerUser;
use App\Entity\Telegram\TelegramBot;
use App\Enum\Feedback\Rating;

class FeedbackTransfer
{
    public function __construct(
        private ?MessengerUser $messengerUser = null,
        private ?SearchTermsTransfer $searchTerms = null,
        private ?Rating $rating = null,
        private ?string $description = null,
        private ?array $media = null,
        private ?TelegramBot $telegramBot = null,
    )
    {
        $this->searchTerms = $searchTerms ?? new SearchTermsTransfer();
    }

    public function getMessengerUser(): ?MessengerUser
    {
        return $this->messengerUser;
    }

    public function setMessengerUser(?MessengerUser $messengerUser): self
    {
        $this->messengerUser = $messengerUser;

        return $this;
    }

    public function getSearchTerms(): SearchTermsTransfer
    {
        return $this->searchTerms;
    }

    public function getRating(): ?Rating
    {
        return $this->rating;
    }

    public function setRating(?Rating $rating): self
    {
        $this->rating = $rating;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getTelegramBot(): ?TelegramBot
    {
        return $this->telegramBot;
    }

    public function setTelegramBot(?TelegramBot $telegramBot): self
    {
        $this->telegramBot = $telegramBot;

        return $this;
    }

    public function getMedia(): ?array
    {
        return $this->media;
    }

    public function setMedia(?array $media): self
    {
        $this->media = $media;

        return $this;
    }
}
