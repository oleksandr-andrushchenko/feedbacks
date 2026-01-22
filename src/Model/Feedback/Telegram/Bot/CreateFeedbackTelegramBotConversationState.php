<?php

declare(strict_types=1);

namespace App\Model\Feedback\Telegram\Bot;

use App\Enum\Feedback\Rating;
use App\Model\Telegram\TelegramBotConversationState;
use App\Transfer\Feedback\SearchTermsTransfer;

class CreateFeedbackTelegramBotConversationState extends TelegramBotConversationState
{
    public function __construct(
        ?int $step = null,
        private ?SearchTermsTransfer $searchTerms = null,
        private ?Rating $rating = null,
        private ?string $description = null,
        private ?string $createdId = null,
    )
    {
        parent::__construct($step);

        $this->setSearchTerms($this->searchTerms);
    }

    public function getSearchTerms(): SearchTermsTransfer
    {
        return $this->searchTerms;
    }

    public function setSearchTerms(?SearchTermsTransfer $searchTerms): self
    {
        $this->searchTerms = $searchTerms ?? new SearchTermsTransfer();

        return $this;
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

    public function getCreatedId(): ?string
    {
        return $this->createdId;
    }

    public function setCreatedId(?string $createdId): self
    {
        $this->createdId = $createdId;

        return $this;
    }
}
